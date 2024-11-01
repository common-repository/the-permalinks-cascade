<?php
namespace ThePermalinksCascade;

/**
 * @package The Permalinks Cascade
 * @copyright Copyright 2022 Luigi Cavalieri.
 * @license https://opensource.org/licenses/GPL-3.0 GPL v3.0
 *
 * @since 1.0
 */
final class AdminController {
    /**
     * @since 1.0
     * @var object
     */
    private $plugin;

    /**
     * @since 1.0
     * @var object
     */
    private $db;

    /**
     * @since 1.0
     * @var object
     */
    private $dataController;

    /**
     * @since 1.0
     * @var string
     */
    private $currentTPCAdminPageID = '';

    /**
     * @since 1.0
     * @param string
     */
    private $taxonomyId;

    /**
     * @since 1.0
     * @param object $plugin
     */
    public function __construct( $plugin ) {
        $this->plugin         = $plugin;
        $this->db             = $plugin->db();
        $this->dataController = $plugin->invokeGlobalObject( 'DataController' );
    }

    /**
     * @since 1.0
     */
    public function handleTPCAdminAjaxRequest() {
        if (! isset( $_POST['tpc_action'] ) ) {
            exit;
        }

        $action = sanitize_key( $_POST['tpc_action'] );

        switch ( $action ) {
            case 'tpc_bulk_edit':
                if (! check_admin_referer( 'tpc_bulk_edit', 'tpc_nonce' ) ) {
                    exit;
                }

                if ( !( isset( $_POST['post_ids'] ) && is_array( $_POST['post_ids'] ) ) ) {
                    exit;
                }

                if (! isset( $_POST['data'] ) ) {
                    exit;
                }

                $data     = array();
                $raw_data = filter_var( $_POST['data'], FILTER_CALLBACK, array( 'options' => 'sanitize_text_field' ) );

                // Parsing data.
                foreach ( $raw_data as $entry ) {
                    if ( strpos( $entry['name'], 'tpc' ) !== 0 ) {
                        exit;
                    }

                    $pointer = &$data;
                    $keys    = explode( '[', $entry['name'] );
                    $array_levels_count = count( $keys );

                    for ( $i = 0; $i < $array_levels_count; $i++ ) {
                        $key = sanitize_key( $keys[$i] );

                        if ( isset( $pointer[$key] ) ) {
                            $pointer = &$pointer[$key];
                        }
                        elseif ( $i + 1 < $array_levels_count ) {
                            $pointer[$key] = array();
                            $pointer       = &$pointer[$key];
                        }
                        else {
                            $pointer[$key] = (int) $entry['value'];
                        }
                    }
                }

                $editBoxController = $this->plugin->invokeGlobalObject( 'EditBoxController' );
                $editBoxController->processBulkEditAction( $data['tpc'] );
                break;

            case 'enable_automatic_pinging':
                if (! isset( $_POST['enable_ap'] ) ) {
                    exit;
                }

                $automatic_pinging_on = (bool) (int) $_POST['enable_ap'];

                if (! $this->db->setOption( 'automatic_pinging_on', $automatic_pinging_on ) ) {
                    exit;
                }
                break;

            default:
                /**
                 * @since 1.0
                 */
                do_action( 'tpc_is_doing_admin_ajax', $action );
                break;
        }

        exit( 'ok' );
    }

    /**
     * @since 1.0
     */
    public function wpDidFinishLoading() {
        $this->listenForUserAction();
        $this->registerActions();
        $this->registerTopicTaxonomyWithLabels();
    }

    /**
     * @since 1.0
     */
    private function listenForUserAction() {
        if ( $_POST && isset( $_POST['tpc_page'] ) ) {
            $page_id = sanitize_key( $_POST['tpc_page'] );
        }
        elseif ( $_GET && isset( $_GET['page'], $_GET['tpc_nonce'] ) ) {
            $namespaced_page_id = sanitize_key( $_GET['page'] );
            $page_id            = str_replace( 'tpc-', '', $namespaced_page_id );
        }
        else {
            return false;
        }

        $this->plugin->load( 'admin/page-view-delegate-protocols.php' );
        $this->plugin->load( 'admin/page-controller-classes.php' );

        $page = $this->dataController->page( $page_id );
        
        if (! $page ) {
            wp_die( __( 'Request sent to a non existent page.', 'the-permalinks-cascade' ) );
        }

        if ( !( isset( $_REQUEST['action'] ) && current_user_can( 'manage_options' ) ) ) {
            wp_die( 'You are being a bad fellow.' );
        }
            
        if ( is_multisite() && !is_super_admin() ) {
            wp_die( 'You are being a bad fellow.' );
        }

        $action_id      = sanitize_key( $_REQUEST['action'] );
        $pageController = PageController::makeController( $page,
                                                                  $this->plugin,
                                                                  $this->dataController );

        if (! check_admin_referer( $action_id, 'tpc_nonce' ) ) {
            wp_die( 'You are being a bad fellow.' );
        }

        $redirect_url = $pageController->performUserAction( $action_id );

        if (! $redirect_url ) {
            /**
             * @since 1.0
             */
            $redirect_url = apply_filters( 'tpc_admin_controller_will_redirect_on_unknown_user_action', 
                                           $redirect_url, $action_id, $pageController );
        }

        if ( true === $redirect_url ) {
            wp_redirect( $pageController->pageURL() );
        }
        elseif ( false === filter_var( $redirect_url, FILTER_VALIDATE_URL ) ) {
            wp_die( 'Unknown action.' );
        }
        else {
            wp_redirect( $redirect_url );
        }
        
        exit;
    }

    /**
     * @since 1.0
     */
    private function registerActions() {
        global $pagenow;

        add_action( 'admin_menu', array( $this, 'registerAdminPages' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueueStylesAndScripts' ) );

        switch ( $pagenow ) {
            case 'post.php':
            case 'post-new.php':
                $this->plugin->load( 'admin/field-view.class.php' );
                $this->plugin->load( 'admin/meta-box-controller.class.php' );
                
                $metaBoxController = new MetaBoxController( $this->plugin );
                
                add_action( 'add_meta_boxes', array( $metaBoxController, 'wpDidAddMetaBoxes' ), 10, 2 );
                add_action( 'edit_attachment', array( $this, 'wpDidModifyAttachment' ), 100 );
                add_action( 'delete_attachment', array( $this, 'wpDidModifyAttachment' ), 100 );
                
                // When the POST request is sent from 'post-new.php',
                // sometimes WordPress doesn't invoke wpDidSavePost()
                // if it has been registered with a priority higher than 20.
                add_action( 'save_post', array( $metaBoxController, 'wpDidSavePost' ), 20, 2 );
                add_action( 'trashed_post', array( $this, 'wpDidTrashPost' ), 100 );
                add_action( 'untrashed_post', array( $this, 'wpDidTrashPost' ), 100 );
                break;

            case 'edit.php':
                $post_type = ( isset( $_REQUEST['post_type'] ) ? sanitize_key( $_REQUEST['post_type'] ) : 'post' );

                $this->plugin->load( 'admin/field-view.class.php' );

                $editBoxController = $this->plugin->invokeGlobalObject( 'EditBoxController' );

                add_action( 'admin_print_styles', array( $this, 'wpIsPrintingAdminStyles' ) );
                add_action( 'bulk_edit_custom_box', array( $editBoxController, 'displayBulkEditBox' ), 100, 2 );
                add_action( 'manage_posts_custom_column', array( $this, 'wpNeedsCustomColumnData' ), 10, 2 );
                add_action( 'manage_pages_custom_column', array( $this, 'wpNeedsCustomColumnData' ), 10, 2 );
                add_action( 'trashed_post', array( $this, 'wpDidTrashPost' ), 100 );
                add_action( 'untrashed_post', array( $this, 'wpDidTrashPost' ), 100 );
                add_filter( "manage_{$post_type}_posts_columns", array( $this, 'wpAdminColumns') );

                if ( $post_type == 'page' ) {
                    add_action( 'quick_edit_custom_box', array( $editBoxController, 'displayQuickEditBox' ), 100, 2 );
                }
                break;

            case 'plugins.php':
                $filter_name = 'plugin_action_links_' . $this->plugin->basename();

                add_filter( $filter_name, array( $this, 'addDashboardLinkToActionLinks' ) );
                add_filter( 'plugin_row_meta', array( $this, 'addDonateLinkToPluginRowMeta' ), 10, 2 );
                break;

            case 'edit-tags.php':
                if (! isset( $_REQUEST['taxonomy'] ) ) {
                    break;
                }

                $this->taxonomyId = sanitize_key( $_REQUEST['taxonomy'] );

                if (! taxonomy_exists( $this->taxonomyId ) ) {
                    break;
                }

                add_action( 'edit_' . $this->taxonomyId, array( $this, 'wpDidModifyTaxonomy' ), 100 );
                add_action( 'create_' . $this->taxonomyId, array( $this, 'wpDidModifyTaxonomy' ), 100 );
                add_action( 'delete_' . $this->taxonomyId, array( $this, 'wpDidModifyTaxonomy' ), 100 );
                break;
                
            case 'user-new.php':
                add_action( 'user_register', array( $this, 'wpDidModifyUserProfile' ), 100 );
                break;
                
            case 'user-edit.php':
            case 'profile.php': 
                add_action( 'profile_update', array( $this, 'wpDidModifyUserProfile' ), 100 );
                break;
                
            case 'users.php':
                add_action( 'delete_user', array( $this, 'wpDidModifyUserProfile' ), 100 );
                break;
        }
    }

    /**
     * @since 1.0
     */
    private function registerTopicTaxonomyWithLabels() {
        $this->registerTopicTaxonomy( true );
    }

    /**
     * @since 1.0
     */
    public function registerTopicTaxonomy( $load_labels = false ) {
        $arguments = array(
            'labels'             => array(),
            'public'             => false,
            'show_ui'            => true,
            'show_tagcloud'      => false,
            'show_admin_column'  => true,
            'show_in_quick_edit' => false,
            'rewrite'            => false
        );

        if ( $load_labels ) {
            $arguments['labels'] = array(
                'name'                       => __( 'Topics', 'the-permalinks-cascade' ),
                'singular_name'              => __( 'Topic', 'the-permalinks-cascade' ),
                'all_items'                  => __( 'All Topics', 'the-permalinks-cascade' ),
                'edit_item'                  => __( 'Edit Topic', 'the-permalinks-cascade' ),
                'view_item'                  => __( 'View Topic', 'the-permalinks-cascade' ),
                'update_item'                => __( 'Update Topic', 'the-permalinks-cascade' ),
                'add_new_item'               => __( 'Add New Topic', 'the-permalinks-cascade' ),
                'new_item_name'              => __( 'New Topic', 'the-permalinks-cascade' ),
                'search_items'               => __( 'Search Topics', 'the-permalinks-cascade' ),
                'popular_items'              => __( 'Popular Topics', 'the-permalinks-cascade' ),
                'separate_items_with_commas' => __( 'Separate Topics with commas', 'the-permalinks-cascade' ),
                'add_or_remove_items'        => __( 'Add or remove Topics', 'the-permalinks-cascade' ),
                'choose_from_most_used'      => __( 'Choose from the most used Topics', 'the-permalinks-cascade' ),
                'not_found'                  => __( 'No Topics found', 'the-permalinks-cascade' ),
                'choose_from_most_used'      => __( 'Choose from the most used Topics', 'the-permalinks-cascade' ),
                'back_to_items'              => __( 'Back to Topics', 'the-permalinks-cascade' )
            );
        }

        register_taxonomy( $this->db->prefixDBKey( 'topic' ), 'page', $arguments );
    }

    /**
     * @since 1.0
     */
    public function registerAdminPages() {
        $pages     = $this->dataController->pages( false );
        $dashboard = $pages[0];
        
        if ( $dashboard->namespacedID() === $dashboard->parentSlug() ) {
            $menu_title_ns = '';

            add_menu_page( 'The Permalinks Cascade', 'The Permalinks Cascade', 'manage_options', $dashboard->namespacedID(), 
                           '__return_false', $this->getBase64MenuIcon(), 90 );
        }
        else {
            $menu_title_ns = 'TPC - ';
        }

        foreach ( $pages as $page ) {
            $page_ns_id = $page->namespacedID();
            
            if ( isset( $_GET['page'] ) && ( $page_ns_id == $_GET['page'] ) ) {
                $this->plugin->load( 'admin/field-view.class.php' );
                $this->plugin->load( 'admin/page-view.class.php' );
                $this->plugin->load( 'admin/page-view-delegate-protocols.php' );
                $this->plugin->load( 'admin/page-controller-classes.php' );

                if ( $page->viewClass() !== 'PageView' ) {
                    $this->plugin->load( 'admin/' . $page->id() . '-page-view.class.php' );
                }

                $this->currentTPCAdminPageID = $page_ns_id;

                $pageController     = PageController::makeController( $page, $this->plugin, $this->dataController );
                $menu_page_callback = array( $pageController->loadPageView(), 'display' );

                add_action( 'admin_print_footer_scripts', array( $this, 'wpIsPrintingFooterScripts' ) );
            }
            else {
                $menu_page_callback = '__return_false';
            }

            $menu_title = $menu_title_ns . $page->menuTitle();

            add_submenu_page( $page->parentSlug(), $page->title(), $menu_title, 'manage_options', 
                              $page_ns_id, $menu_page_callback );
        }
    }

    /**
     * @since 1.0
     * @param string $hook
     */
    public function enqueueStylesAndScripts( $hook ) {
        $version                  = $this->plugin->version();
        $current_admin_section_id = ( $this->currentTPCAdminPageID ? 'tpc-admin' : $hook );
        
        switch ( $current_admin_section_id ) {
            case 'edit.php':
                wp_enqueue_script( 
                    'the-permalinks-cascade',
                    $this->plugin->dirURL( 'resources/edit-box.js' ),
                    array(),
                    $version,
                    true
                );
                break;

            case 'tpc-admin':
                wp_enqueue_style(
                    'the-permalinks-cascade',
                    $this->plugin->dirURL( 'resources/the-permalinks-cascade.css' ),
                    null,
                    $version
                );
                wp_enqueue_script(
                    'the-permalinks-cascade',
                    $this->plugin->dirURL( 'resources/the-permalinks-cascade.js' ),
                    array( 'jquery-ui-sortable' ),
                    $version
                );
                break;
        }
    }

    /**
     * @since 1.0
     *
     * @param array $action_links
     * @return array
     */
    public function addDashboardLinkToActionLinks( $action_links ) {
        $action_links['dashboard'] = '<a href="' . $this->dashboardURL()
                                   . '">' . __( 'Dashboard', 'the-permalinks-cascade' )
                                   . '</a>';

        return $action_links;
    }

    /**
     * @since 2.0
     *
     * @param array $plugin_meta
     * @param string $plugin_file
     * @return array
     */
    public function addDonateLinkToPluginRowMeta( $plugin_meta, $plugin_file ) {
        if ( $this->plugin->basename() == $plugin_file ) {
            $plugin_meta[] = '<a href="' . $this->plugin->pluginURI() . '">' . __( 'Donate', 'the-permalinks-cascade' ) . '</a>';
        }
        
        return $plugin_meta;
    }

    /**
     * @since 1.0
     */
    public function dashboardURL() {
        $dashboard = $this->dataController->page( 'dashboard' );
        $arguments = array( 'page' => $dashboard->namespacedID() );  

        return add_query_arg( $arguments, admin_url( $dashboard->parentSlug() ) );
    }

    /**
     * @since 1.0
     */
    public function wpIsPrintingAdminStyles() { 
        echo <<<STYLES
<style>
#tpc-bulk-edit-box .tpc-exclude_from-control-title { width: 10em; }
#tpc-bulk-edit-box .tpc-control-title { width: 13em; }

.tpc-exclusion-badge {
    border-radius: 8px;
    color: #fff;
    cursor: help;
    display: block;
    float: left;
    font-size: 10px;
    font-weight: 600;
    line-height: 16px;
    margin: 3px 7px 0 0;
    text-align: center;
    width: 16px;
}

.tpc-is_ghost_page-badge {
    padding: 0 10px;
    width: auto;
}

.tpc-site_tree-exclusion-badge { background: #d75b00; }
.tpc-sitemap-exclusion-badge { background: #0093bf; }
.tpc-newsmap-exclusion-badge { background: #a000bd; }
.tpc-hyperlists-exclusion-badge,
.tpc-is_ghost_page-badge { background: #444; }
</style>
STYLES;
    }

    /**
     * @since 1.0
     */
    public function wpIsPrintingFooterScripts() {
        echo '<script>ThePermalinksCascade.init("', esc_attr( $this->currentTPCAdminPageID ),
             '", {sftEnableBtnTitle:"', esc_attr__( 'Enable Reordering', 'the-permalinks-cascade' ),
             '", sftCancelBtnTitle:"', esc_attr__( 'Cancel', 'the-permalinks-cascade' ),
             '", sftSaveBtnTitle:"', esc_attr__( 'Save', 'the-permalinks-cascade' ), 
             '", sortableFieldsetTooltip:"', esc_html__( 'Drag the content types to reorder the hyper-lists.', 'the-permalinks-cascade' ),
             '"});</script>';
    }

    /**
     * @since 1.0
     *
     * @param string $column_name
     * @return int $post_id
     * @return bool
     */
    public function wpNeedsCustomColumnData( $column_name, $post_id ) {
        if ( $column_name != 'tpc_exclusions' ) {
            return false;
        }

        if ( $this->db->getPostMeta( $post_id, 'is_ghost_page' ) ) {
            echo '<span class="tpc-exclusion-badge tpc-is_ghost_page-badge" title="', 
                 esc_attr__( 'This is a Ghost Page', 'the-permalinks-cascade' ), '">', 
                 esc_html__( 'Everywhere', 'the-permalinks-cascade' ), '</span>';

            return true;
        }

        $badges_displayed          = false;
        $resource_types_dictionary = array(
            'site_tree'  => array( 'S', 'Site Tree' ),
            'sitemap'    => array( 'G', 'Google Sitemap' ),
            'newsmap'    => array( 'N', 'Google News Sitemap' ),
            'hyperlists' => array( 'H', __( 'All Hyper-lists', 'the-permalinks-cascade' ) )
        );

        foreach ( $resource_types_dictionary as $resource_slug => $displayables ) {
            $key = 'exclude_from_' . $resource_slug;

            if ( $this->db->getPostMeta( $post_id, $key ) ) {
                $badges_displayed = true;

                echo '<span class="tpc-exclusion-badge tpc-', esc_attr( $resource_slug ), 
                     '-exclusion-badge" title="', esc_attr( $displayables[1] ), '">', esc_html( $displayables[0] ), '</span> ';
            }
        }

        if (! $badges_displayed ) {
            echo '&mdash;';
        }

        return true;
    }

    /**
     * @since 1.0
     *
     * @param array $columns
     * @return array
     */
    public function wpAdminColumns( $columns ) {
        $tax_col_key = 'taxonomy-' . $this->db->prefixDBKey( 'topic' );
        
        $columns['tpc_exclusions'] = __( 'Excluded From', 'the-permalinks-cascade' );

        if ( isset( $columns[$tax_col_key] ) ) {
            $columns[$tax_col_key] = __( 'Topic', 'the-permalinks-cascade' );
        }
        
        return $columns;
    }

    /**
     * @since 1.0
     *
     * @param string $post_id
     * @param object $post
     * @return bool
     */
    public function wpDidSavePostViaRestRequest( $post_id, $post ) {
        if ( !( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
            return false;
        }

        if (! current_user_can( 'edit_post', $post_id ) ) {
            return false;
        }

        if ( 
            ( 'publish' == $post->post_status ) &&
            $this->plugin->isSitemapActive( 'sitemap' ) &&
            ( get_option( 'page_on_front' ) == $post_id )
        ) {
            $this->plugin->flushCachedData( 'sitemap' );
        }

        return true;
    }

    /**
     * @since 1.0
     *
     * @param string $post_id
     * @param object $post
     * @return bool
     */
    public function wpDidSavePostViaAjax( $post_id, $post ) {
        if ( !( isset( $_POST['tpc'] ) && is_array( $_POST['tpc'] ) ) ){
            return false;
        }

        if ( 
            !(
                ( 'page' == $post->post_type ) &&
                isset( $_POST['tpc']['topic_id'] ) &&
                check_admin_referer( 'assign_topic', 'tpc_nonce' ) &&
                current_user_can( 'edit_post', $post_id )
            )
        ) {
            return false;
        }

        $topic_id = (int) $_POST['tpc']['topic_id'];

        $editBoxController = $this->plugin->invokeGlobalObject( 'EditBoxController' );
        $editBoxController->assignTopicToPage( $topic_id, $post );

        return true;
    }

    /**
     * @since 1.0
     * @param int $post_id
     */
    public function wpDidTrashPost( $post_id ) {
        $post = get_post( $post_id );

        if (! $post ) {
            return false;
        }

        if (
            $this->plugin->isSitemapActive( 'site_tree' ) &&
            $this->plugin->isContentTypeIncluded( $post->post_type, 'site_tree' ) && 
            !$this->db->getPostMeta( $post->ID, 'exclude_from_site_tree' )
        ) {
            $this->plugin->flushCachedData( 'site_tree' );
        }
        
        if (
            $this->plugin->isSitemapActive( 'sitemap' ) &&
            $this->plugin->isContentTypeIncluded( $post->post_type, 'sitemap' )
        ) {
            $this->plugin->flushCachedData( 'sitemap' );
        }

        if ( 
            $this->plugin->isSitemapActive( 'newsmap' ) &&
            $this->plugin->isContentTypeIncluded( $post->post_type, 'newsmap' )
        ) {
            $this->plugin->flushCachedData( 'newsmap' );
        }
    }
    
    /**
     * @since 1.0
     * @param int $user_id
     */
    public function wpDidModifyUserProfile( $user_id ) {
        if (
            $this->plugin->isSitemapActive( 'site_tree' ) &&
            (
                $this->plugin->isContentTypeIncluded( 'authors', 'site_tree' ) || 
                ( $this->db->getOption( 'group_by', false, 'post', 'site_tree' ) == 'author' )
            )
        ) {
            $excluded_authors = explode( ', ', $this->db->getOption( 'exclude', '', 'authors', 'site_tree' ) );

            if ( 
                !( 
                    $excluded_authors && 
                    in_array( get_userdata( $user_id )->user_nicename, $excluded_authors )
                ) 
            ) {
                $this->plugin->flushCachedData( 'site_tree' );
            }            
        }
        
        if (
            $this->plugin->isSitemapActive( 'sitemap' ) &&
            $this->plugin->isContentTypeIncluded( 'authors', 'sitemap' )
        ) {
            $this->plugin->flushCachedData( 'sitemap' );
        }
    }
    
    /**
     * @since 1.0
     * @param int $term_id
     */ 
    public function wpDidModifyTaxonomy( $term_id ) {
        if ( 
            $this->plugin->isSitemapActive( 'site_tree' ) &&
            $this->plugin->isContentTypeIncluded( $this->taxonomyId, 'site_tree' )
        ) {
            $excluded_ids = $this->db->getOption( 'exclude', '', $this->taxonomyId, 'site_tree' );

            if ( !( $excluded_ids && in_array( $term_id, wp_parse_id_list( $excluded_ids ) ) ) ) {
                $this->plugin->flushCachedData( 'site_tree' );
            }
        }

        if (
            $this->plugin->isSitemapActive( 'sitemap' ) &&
            $this->plugin->isContentTypeIncluded( $this->taxonomyId, 'sitemap' )
        ) {
            $excluded_ids = $this->db->getOption( 'exclude', '', $this->taxonomyId, 'sitemap' );

            if ( !( $excluded_ids && in_array( $term_id, wp_parse_id_list( $excluded_ids ) ) ) ) {
                $this->plugin->flushCachedData( 'sitemap' );
            }
        }
    }

    /**
     * @since 1.0
     * @param int $attachment_id
     */
    public function wpDidModifyAttachment( $attachment_id ) {
        if ( $this->plugin->isSitemapActive( 'sitemap' ) ) {
            $attachment = get_post( $attachment_id );
        
            if ( 
                $attachment && 
                $attachment->post_parent &&
                !$this->db->getPostMeta( $attachment->post_parent, 'exclude_from_sitemap' )
            ) {
                $this->plugin->flushCachedData( 'sitemap' );
            }
        }
    }

    /**
     * @since 1.0
     * @return string
     */
    public function getBase64MenuIcon() {
        return 'data:image/svg+xml;base64,PHN2ZyBpZD0iQWRtaW5fTWVudV9JY29uIiBkYXRhLW5hbWU9IkFkbWluIE1lbnUgSWNvbiIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB3aWR0aD0iMjU2IiBoZWlnaHQ9IjI1NiIgdmlld0JveD0iMCAwIDI1NiAyNTYiPgogIDxkZWZzPgogICAgPHN0eWxlPgogICAgICAuY2xzLTEgewogICAgICAgIGZpbGw6ICM5ZWEzYTg7CiAgICAgICAgZmlsbC1ydWxlOiBldmVub2RkOwogICAgICB9CiAgICA8L3N0eWxlPgogIDwvZGVmcz4KICA8cGF0aCBpZD0iUmVjdGFuZ2xlXzVfY29weV8yIiBkYXRhLW5hbWU9IlJlY3RhbmdsZSA1IGNvcHkgMiIgY2xhc3M9ImNscy0xIiBkPSJNMTkyLDE2LjY1MkwyNTYsMTI3LjUsMTkyLDIzOC4zNDhhMTcyLjAzMSwxNzIuMDMxLDAsMCwwLTEyOCwwTDAsMTI3LjUsNjQsMTYuNjUyQTE2MC4wNzUsMTYwLjA3NSwwLDAsMCwxMjgsMzAsMTYwLjA3NiwxNjAuMDc2LDAsMCwwLDE5MiwxNi42NTJaTTc1LDE1NkgxODFhMTIsMTIsMCwwLDEsMCwyNEg3NUExMiwxMiwwLDAsMSw3NSwxNTZaTTU1LDExNkgyMDFhMTIsMTIsMCwwLDEsMCwyNEg1NUExMiwxMiwwLDAsMSw1NSwxMTZaTTc1LDc2SDE4MWExMiwxMiwwLDAsMSwwLDI0SDc1QTEyLDEyLDAsMCwxLDc1LDc2WiIvPgo8L3N2Zz4K';
    }
}