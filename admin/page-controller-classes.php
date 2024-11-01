<?php
namespace ThePermalinksCascade;

/**
 * @package The Permalinks Cascade
 * @copyright Copyright 2022 Luigi Cavalieri.
 * @license https://opensource.org/licenses/GPL-3.0 GPL v3.0
 *
 * @since 1.0
 */
class PageController implements PageViewDelegateProtocol {
    /**
     * @since 1.0
     * @var object
     */
    protected $plugin;

    /**
     * @since 1.0
     * @var object
     */
    protected $db;

    /**
     * @since 1.0
     * @var object
     */
    protected $dataController;
    
    /**
     * @since 1.0
     * @var object
     */
    protected $page;
    
    /**
     * @since 1.0
     *
     * @param object $page
     * @param object $plugin
     * @param object $dataController
     * @return object
     */
    public static function makeController( $page, $plugin, $dataController = null ) {
        $base_class       = __CLASS__;
        $controller_class = __NAMESPACE__ . '\\' . $page->controllerClass();

        $controller                 = new $controller_class;
        $controller->page           = $page;
        $controller->plugin         = $plugin;
        $controller->db             = $plugin->db();
        $controller->dataController = $dataController;

        if ( $controller instanceof $base_class ) {
            return $controller;
        }

        $message = __METHOD__ . '() cannot create objects of class ' . $controller_class;
        
        trigger_error( $message, E_USER_ERROR );
    }

    /**
     * @since 1.0
     */
    protected function __construct() {}

    /**
     * @since 1.0
     * @return object
     */
    public function getPage() {
        return $this->page;
    }

    /**
     * @since 1.0
     * @return object
     */
    public function loadPageView() {
        $sections = $this->dataController->loadPageSections( $this->page->id() );
        
        $pageView = PageView::makeView( $this->page );
        $pageView->setSections( $sections );
        $pageView->setDelegate( $this );

        return $pageView;
    }

    /**
     * @since 1.0
     *
     * @param string $action
     * @return bool
     */
    public function performUserAction( $action ) {
        if ( $action != 'update_settings' ) {
            return false;
        }
        
        $raw_options = array();
        $page_id     = $this->page->id();

        if ( isset( $_POST['tpc'] ) && is_array( $_POST['tpc'] ) ) {
            $raw_options = filter_var( $_POST['tpc'], FILTER_CALLBACK, array( 'options' => 'wp_kses_post' ) );   
        }
        
        $options = $this->dataController->validateOptions( $raw_options, $this->page );
        
        switch ( $page_id ) {
        	case 'site_tree':
        		$notice_text = __( 'Settings saved. %sView Site Tree%s', 'the-permalinks-cascade' );
                $link_opening_tag = '<a href="' . $this->plugin->sitemapURL( $page_id ) . '" target="tpc_admin">';

                $this->registerNotice( sprintf( $notice_text, $link_opening_tag, '</a>' ) );
	            break;

	        default:
                $this->registerNotice( __( 'Settings saved.', 'the-permalinks-cascade' ) );
	        	break;
        }
        
        $this->db->setOptions( $options );
        $this->plugin->flushCachedData( $page_id );

        return true;
    }
    
    /**
     * @since 1.0
     *
     * @param string $notice
     * @param string $type
     */
    protected function registerNotice( $notice, $type = 'success' ) {
        $data = array(
            'message' => $notice,
            'type'    => $type
        );

        set_transient( $this->db->prefixDBKey( 'admin_notice' ), $data, 30 );
    }

    /**
     * @since 1.0
     */
    protected function displayNotice() {
        $transient_key = $this->db->prefixDBKey( 'admin_notice' );
        $notice        = get_transient( $transient_key );

        if ( $notice && is_array( $notice ) ) {
            $plugin_id = $this->plugin->id();

            add_settings_error( $plugin_id, $plugin_id, $notice['message'], $notice['type'] );
            settings_errors( $plugin_id );
            delete_transient( $transient_key );
        }
    }

    /**
     * @since 1.0
     *
     * @param array $query_arguments
     * @param string $page_id
     * @return string
     */
    public function pageURL( $query_arguments = array(), $page_id = '' ) {
        if (! $page_id ) {
            $page = $this->page;
        }
        else {
            $page = $this->dataController->page( $page_id );

            if (! $page ) {
                return '';
            }
        }

        $arguments = array( 'page' => $page->namespacedID() );
        
        if ( $query_arguments ) {
            $arguments += $query_arguments;
            
            if ( isset( $arguments['action'] ) ) {
                $arguments['tpc_nonce'] = wp_create_nonce( $arguments['action'] );
            }
        }

        if ( '.php' === substr( $page->parentSlug(), -4 ) ) {
            $admin_url = admin_url( $page->parentSlug() );
        }
        else {
            $admin_url = admin_url( 'admin.php' );
        }

        return add_query_arg( $arguments, $admin_url );
    }
    
    /**
     * {@inheritdoc}
     */
    public function pageViewWillDisplayForm( $pageView ) {
        $this->displayNotice();
    }
    
    /**
     * {@inheritdoc}
     */
    public function pageViewFormAction( $pageView ) { 
    	return 'update_settings';
    }
    
    /**
     * {@inheritdoc}
     */
    public function pageViewFieldValue( $field, $section_id ) {
    	$context = ( $this->page->id() == 'site_tree' ) ? $this->page->id() : '';
        $value   = $this->db->getOption( $field->id(), $field->defaultValue(), $section_id, $context );
        $filter  = new OptionsFilter( $value, $field );
        
        return $filter->filterOption();
    }
}


/**
 * @since 1.0
 */
final class DashboardController
    extends PageController
 implements DashboardDelegateProtocol {
    /**
     * @since 1.0
     */
    private $configMode;

    /**
     * @since 1.0
     */
    private $showLicenceKeyErrorMsg;

    /**
     * @since 1.0
     */
    protected function __construct() {
        if ( isset( $_GET['config'] ) ) {
    		$this->configMode = sanitize_key( $_GET['config'] );
    	}
    }

    /**
     * @see parent::performUserAction()
     * @since 1.0
     */
    public function performUserAction( $action ) {
        switch ( $action ) {
            case 'send_pings':
                if (! isset( $_GET['sitemap_id'] ) ) {
                    return false;
                }

                if ( $this->plugin->isWebsiteLocal() ) {
                    return false;
                }

                $sitemap_id = sanitize_key( $_GET['sitemap_id'] );
                
                $this->plugin->invokeGlobalObject( 'PingController' )->ping( $sitemap_id );
                break;

            case 'configure':
            	if (! $this->doConfigureAction() ) {
            		return false;
            	}
            	break;
            
            case 'deactivate':
                if (! isset( $_POST['tpc_form_id'] ) ) {
                    return false;
                }

                $form_id = sanitize_key( $_POST['tpc_form_id'] );

                switch( $form_id ) {
                    case 'sitemap':
                    case 'newsmap':
                        $this->db->setOption( $form_id, false, 'is_sitemap_active' );
                        flush_rewrite_rules( false );      
                        break;

                    case 'site_tree':
                        $this->db->setOption( 'page_for_site_tree', 0 );
                        break;

                    default:
                        return false;
                }

                $this->plugin->flushCachedData( $form_id );
                break;

            default:
                return false;
        }

        return true;
    }

    /**
     * @since 1.0
     * @return bool
     */
    public function doConfigureAction() {
        if (! isset( $_POST['tpc_form_id'] ) ) {
            return false;
        }
        
        $raw_options = array();

        if ( isset( $_POST['tpc'] ) && is_array( $_POST['tpc'] ) ) {
            $raw_options = filter_var( $_POST['tpc'], FILTER_CALLBACK, array( 'options' => 'sanitize_text_field' ) );   
        }

        $form_id           = sanitize_key( $_POST['tpc_form_id'] );
        $sanitised_options = $this->dataController->validateOptions( $raw_options, $this->page, $form_id );
        $config_options    = $sanitised_options[$form_id];
        $sitemap_active    = $this->plugin->isSitemapActive( $form_id );

        $sitemap_filename_has_changed = false;

        switch ( $form_id ) {
            case 'site_tree':
                $old_site_tree_id = (int) $this->db->getOption( 'page_for_site_tree' );
                break;

            case 'sitemap':
                $old_sitemap_filename = $this->db->getOption( 'sitemap_filename' );
                break;
        }
        
        $content_types_id = $form_id . '_content_types';
        $content_flags    = $config_options[$content_types_id];
        $at_least_one_content_type_is_included = false;

        foreach ( $content_flags as $content_type_included ) {
            if ( $content_type_included ) {
                $at_least_one_content_type_is_included = true;

                break;
            }
        }

        if (! $at_least_one_content_type_is_included ) {
            if ( 'newsmap' == $form_id ) {
                $config_options[$content_types_id]['post'] = true;
            }
            else {
                $config_options[$content_types_id]['page'] = true;
            }
        }

        if (
            ( !$this->db->setOptions( $config_options ) && $sitemap_active ) ||
            ( !$sitemap_active && isset( $_POST['save_order'] ) )            
        ) {
            return true;
        }

        if ( $sitemap_active ) {
            $this->plugin->flushCachedData( $form_id );
        }
        
        switch ( $form_id ) {
            case 'site_tree':
                $content_options     = array();
                $old_content_options = $this->db->getOption( $form_id );
                $defaults            = $this->dataController->defaultsForPage( $form_id );

                if ( is_array( $old_content_options ) ) {
                    $content_options[$form_id] = array_merge( $defaults[$form_id], $old_content_options );
                }
                else {
                    $content_options[$form_id] = $defaults[$form_id];
                }

                $this->db->setOptions( $content_options );

                $site_tree_id = $config_options['page_for_site_tree'];

                if ( $site_tree_id != $old_site_tree_id ) {
                    if ( $old_site_tree_id > 0 ) {
                        $this->db->deletePostMeta( $old_site_tree_id, 'exclude_from_site_tree' );
                    }

                    if ( $site_tree_id > 0 ) {
                        $this->db->setPostMeta( $site_tree_id, 'exclude_from_site_tree', true );
                    }
                }
                break;

            case 'sitemap':
                $sitemap_filename_has_changed = ( $config_options['sitemap_filename'] != $old_sitemap_filename );
                // Break omitted.

            case 'newsmap':   
                if ( !$sitemap_active || $sitemap_filename_has_changed ){
                    $this->db->setOption( $form_id, true, 'is_sitemap_active' );
                    $this->plugin->registerRewriteRules();

                    flush_rewrite_rules( false );
                }
                break;

            default:
                return false;
        }

        if ( $this->configMode ) {
            $message = __( 'Configuration saved.', 'the-permalinks-cascade' );

            if ( $sitemap_filename_has_changed ) {
                $link_opening_tag = '<a href="https://search.google.com/search-console/about">';

                $message .= ' ';
                $message .= __( 'Please note that as you changed the filename of the Google Sitemap, you shall re-submit its URL on %1$sthe Google Search Console%2$s.', 'the-permalinks-cascade' );
                $message = sprintf( $message, $link_opening_tag, '</a>' );
            }
            
            $this->registerNotice( $message );     
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function pageViewFormAction( $pageView ) {
    	$form_id = $pageView->formID();

    	if ( $this->plugin->isSitemapActive( $form_id ) && ( $this->configMode != $form_id ) ) {
            return 'deactivate';
        }

        return 'configure';
    }

    /**
     * {@inheritdoc}
     */
    public function dashboardWillDisplayToolbarButtons( $dashboardPageView, $form_id ) {
        $config = array();
        
        if ( $this->plugin->isSitemapActive( $form_id ) ) {
            if ( $this->configMode == $form_id ) {
                $config['submit_title'] = __( 'Save Changes', 'the-permalinks-cascade' );
                
                echo '<a href="', esc_url( $this->pageURL() ), '" class="tpc-aux-tb-btn">', 
                     esc_html__( 'Cancel', 'the-permalinks-cascade' ), '</a>';
            }
            else {
                echo '<input type="submit" class="tpc-aux-tb-btn tpc-deactivate-tb-btn tpc-hidden-tb-btn" name="submit" value="', 
                     esc_attr__( 'Deactivate', 'the-permalinks-cascade' ), '">';
 
                $config['view_url']        = $this->plugin->sitemapURL( $form_id );
                $config['config_mode_url'] = $this->pageURL( array( 'config' => $form_id ) );
                $config['settings_url']    = $this->pageURL( array(), $form_id );
            }
        }
        else {
            $config['submit_title'] = __( 'Activate', 'the-permalinks-cascade' );
        }

        $dashboardPageView->configureToolbar( $config );
    }
    
    /**
     * {@inheritdoc}
     */
    public function dashboardCanDisplayMetrics( $dashboardPageView, $form_id ) {
        if (
            !$this->plugin->isSitemapActive( $form_id ) ||
            ( $this->configMode == $form_id )
        ) {
            return false;
        }

        $items_count_metric = (int) $this->db->getNonAutoloadOption( 'metrics', -1, 'tot_items', $form_id );

        switch ( $form_id ) {
            case 'site_tree':
                $dashboardPageView->registerMetric( __( 'Items', 'the-permalinks-cascade' ), $items_count_metric );
                break;

            case 'sitemap':
                $dashboardPageView->registerMetric( __( 'Permalinks', 'the-permalinks-cascade' ), $items_count_metric );

                $tot_images = $this->db->getNonAutoloadOption( 'metrics', -1, 'tot_images', $form_id );
                $tot_videos = $this->db->getNonAutoloadOption( 'metrics', -1, 'tot_videos', $form_id );

                if ( $tot_images > 0 ) {
                    $dashboardPageView->registerMetric( __( 'Images', 'the-permalinks-cascade' ), $tot_images ); 
                }

                if ( $tot_videos > 0 ) {
                    $dashboardPageView->registerMetric( __( 'Videos', 'the-permalinks-cascade' ), $tot_videos ); 
                }
                
                break;

            case 'newsmap':
                $dashboardPageView->registerMetric( __( 'News', 'the-permalinks-cascade' ), $items_count_metric );
                break;
        }

        if ( $this->db->nonAutoloadOptionExists( 'metrics', 'avg_num_queries', $form_id ) ) {
            $key_prefix         = 'avg_';
            $queries_metric_title = __( 'Avg. Queries', 'the-permalinks-cascade' );
            $runtime_metric_title = __( 'Avg. Runtime', 'the-permalinks-cascade' );
        }
        else {
            $key_prefix         = '';
            $queries_metric_title = __( 'Queries', 'the-permalinks-cascade' );
            $runtime_metric_title = __( 'Runtime', 'the-permalinks-cascade' );
        }

        $queries_metric = (int) $this->db->getNonAutoloadOption( 'metrics', -1, "{$key_prefix}num_queries", $form_id );
        $runtime_metric = (float) $this->db->getNonAutoloadOption( 'metrics', 0, "{$key_prefix}runtime", $form_id ) . 's';

        $dashboardPageView->registerMetric( $queries_metric_title, $queries_metric );
        $dashboardPageView->registerMetric( $runtime_metric_title, $runtime_metric );

        $metrics_computed_on = $this->db->getNonAutoloadOption( 'metrics', 0, 'metrics_computed_on', $form_id );
        $metrics_computed_on = utilities\time_since( $metrics_computed_on );
        
        $dashboardPageView->setMetricsFreshness( $metrics_computed_on );

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function dashboardDidDisplayMetrics( $dashboardPageView, $form_id ) {
        if ( 'site_tree' == $form_id ) {
            return false;
        }

        $can_ping             = false;
        $website_is_local     = $this->plugin->isWebsiteLocal();
        $automatic_pinging_on = ( $this->db->getOption( 'automatic_pinging_on' ) || ( $form_id == 'newsmap' ) );

        if (! $website_is_local ) {
            $pingController = $this->plugin->invokeGlobalObject( 'PingController' );
            $info           = $pingController->getPingInfo( $form_id );
            $can_ping       = $pingController->canPingOnRequest( $form_id );
        }

        if ( $website_is_local ) {
            $status_class = 'tpc-ping-notice';
        }
        elseif ( $automatic_pinging_on ) {
            $status_class = 'tpc-automatic-pinging-on';
        }
        else {
            $status_class = 'tpc-automatic-pinging-off';
        }

        echo '<div class="tpc-pinging-bar tpc-self-clear"><div class="tpc-automatic-pinging-ui ', esc_attr( $status_class ),
             '"><div class="tpc-ap-bubble ', esc_attr( $status_class ), '">';
        
        if ( $website_is_local ) {
            echo '<p class="tpc-ap-status">', esc_html__( 'Pinging Disabled', 'the-permalinks-cascade' ), '</p>';
        }
        else {
            $checked_attribute     = '';
            $hidden_off_status_msg = '';
            $hidden_on_status_msg  = ' tpc-ap-status-hidden';

            if ( $automatic_pinging_on ) {
                $checked_attribute     = ' checked';
                $hidden_off_status_msg = $hidden_on_status_msg;
                $hidden_on_status_msg  = '';
            }

            if ( 'sitemap' == $form_id ) {
                echo '<div class="tpc-ap-switch"><input type="checkbox" id="tpc-', esc_attr( $form_id ), 
                     '-aps-control" class="tpc-ap-switch-control"', esc_attr( $checked_attribute ),
                     '><label for="tpc-', esc_attr( $form_id ), '-aps-control"></label></div>';  
            }

            echo '<p class="tpc-ap-status tpc-ap-on-status-msg', esc_attr( $hidden_on_status_msg ), 
                 '">', esc_html__( 'Automatic Pinging ON', 'the-permalinks-cascade' ),
                 '</p><p class="tpc-ap-status tpc-ap-off-status-msg', esc_attr( $hidden_off_status_msg ), 
                 '">', esc_html__( 'Automatic Pinging OFF', 'the-permalinks-cascade' ), '</p>';
        }

        echo '</div></div><p class="tpc-ping-status-msg';

        if ( $website_is_local ) {
            echo ' tpc-psm-on-pinging-disabled">';
            echo esc_html__( "I'm sorry but I cannot send pings from your website, because its address appears to be a known local development environment URL.", 'the-permalinks-cascade' );
        }
        else {
            echo '">';

            if ( isset( $info['ping_failed'] ) && $info['ping_failed'] ) {
                echo '<strong>', esc_html__( 'Warning:', 'the-permalinks-cascade' ), '</strong> ';
            }

            echo wp_kses_post( $info['status_msg'] );
        }

        echo '</p>';

        if ( $can_ping ) {
            $args  = array(
                'action'     => 'send_pings',
                'sitemap_id' => $form_id
            );

            echo '<a href="', esc_url( $this->pageURL( $args ) ),
                 '" class="tpc-ping-btn">', esc_html( $info['ping_btn_title'] ), '</a>';
        }
        elseif (! $website_is_local ) {
            $message = sprintf( __( 'Pinging-on-request idle for %s.', 'the-permalinks-cascade' ), 
                                $pingController->getTimeToNextPingInWords( $form_id ) );

            echo '<p class="tpc-time-to-next-ping">', esc_html( $message ), '</p>';
        }

        echo '</div>';
    }

    /**
     * {@inheritdoc}
     */
    public function dashboardDidDisplaySingleForm( $form_id ) {
        if ( ( 'site_tree' == $form_id ) || !$this->plugin->isSitemapActive( $form_id ) ) {
            return false;
        }

        $pingController = $this->plugin->invokeGlobalObject( 'PingController' );
        $log            = $pingController->getLog( $form_id );

        if ( empty( $log ) ) {
            return false;
        }

        $show_se_column = ( $form_id == 'sitemap' || WP_DEBUG );

        $search_engines_info = array(
            'google' => array( 
                'abbr'      => 'g',
                'long_name' => 'Google'
            ),
            'bing' => array(
                'abbr'      => 'b+y',
                'long_name' => 'Bing + Yahoo!'
            )
        );

        echo '<div class="tpc-pinging-log-panel"><div class="tpc-pl-container">',
             '<table class="tpc-pinging-log"><thead><tr><th class="tpc-pl-small-cell">',
             esc_html__( 'Date', 'the-permalinks-cascade' ), '</th>';

        if ( $show_se_column ) {
            $triggered_by_css_class = 'tpc-pl-large-cell';

            echo '<th class="tpc-pl-very-small-cell">', esc_html__( 'Search Engines', 'the-permalinks-cascade' ), '</th>';
        }
        else {
            $triggered_by_css_class = 'tpc-pl-very-large-cell';
        }

        echo '<th class="', esc_attr( $triggered_by_css_class ), '">', esc_html__( 'Triggered by', 'the-permalinks-cascade' ), 
             '</th><th>', esc_html__( 'Response Code', 'the-permalinks-cascade' ), '</th></tr></thead><tbody>';
        
        foreach ( $log as $entry ) {
            echo '<tr><td class="tpc-pl-small-cell">', esc_html( utilities\gmt_to_local_date( $entry->getTime() ) ), '</td>';

            if ( $show_se_column ) { 
                echo '<td class="tpc-pl-very-small-cell">';
                
                $ids = $entry->getSearchEngineIDs();

                foreach ( $ids as $id ) {
                    echo '<span title="', esc_attr( $search_engines_info[$id]['long_name'] ),
                         '" class="tpc-pl-se tpc-pl-se-', $id,
                         '">', esc_html( $search_engines_info[$id]['abbr'] ), '</span>';
                }

                echo '</td>';
            }

            echo '<td class="', esc_attr( $triggered_by_css_class ), '">';

            $post_id = $entry->getPostID();

            if ( $post_id > 0 ) {
                $post = get_post( $post_id );

                if ( is_object( $post ) ) {
                    echo '<p>', sanitize_text_field( $post->post_title ), '<p>';
                }
                else {
                    echo '-';
                }
            }
            else {
                echo '<p class="tpc-pl-on-request">', esc_html__( 'On request', 'the-permalinks-cascade' ), '</p>';
            }

            echo '</td><td>';

            $code = $entry->getResponseCode();

            switch ( $code ) {          
                case '200':
                    echo '<span class="tpc-pl-code-200">200</span>';
                    break;

                case 'wp_error':
                    echo '<p class="tpc-pl-wp-error">', sanitize_text_field( $entry->getResponseMessage() ), '</p>';
                    break;
                
                default:
                    echo '<strong>', esc_html( $code ), '</strong>';
                    break;
            }

            echo '</td></tr>';

        }
        
        echo '</tbody></table></div><div class="tpc-pl-toggle-container"><button type="button" class="tpc-pl-toggle" onclick="this.parentElement.previousSibling.classList.toggle(\'tpc-pl-expanded\')">';
        echo esc_html__( 'Pinging Log', 'the-permalinks-cascade' ), '</button></div></div>';

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function dashboardDidDisplayForms( $dashboardPageView ) {
        echo '<aside class="tpc-sidebox"><h3>Need Help?</h3>', 
             '<p><a href="' . esc_url( $this->plugin->pluginURI( 'help/' ) ), 
             '">Start From Here!</a></p><p>If you need further assistance, you may <a href="', 
             esc_url( $this->plugin->authorURI( '/contact/' ) ), '">contact the developer directly</a> ', 
             "or post in the plugin's support forum on WordPress.org.</p></aside>", 
             '<aside class="tpc-sidebox"><h3>Support Development</h3>', 
             "<p>If you think The Permalinks Cascade is a helpful software, please consider making a donation to fund ongoing bug fixes, enhancements and the development of new features.</p>", 
             '<a href="', esc_url( $this->plugin->pluginURI() ), '" id="tpc-go-pro-btn">Donate</a></aside>';
    }
}