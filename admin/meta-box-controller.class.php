<?php
namespace ThePermalinksCascade;

/**
 * @package The Permalinks Cascade
 * @copyright Copyright 2022 Luigi Cavalieri.
 * @license https://opensource.org/licenses/GPL-3.0 GPL v3.0
 *
 * @since 1.0
 */
final class MetaBoxController {
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
	 * @var array
	 */
	private $sections = array();
	
	/**
	 * @since 1.0
	 * @param object $plugin
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->db	  = $plugin->db();
	}

	/**
	 * @since 1.0
     *
     * @param string $post_type
	 * @param object $post
	 */
	public function wpDidAddMetaBoxes( $post_type, $post ) {
        if ( $this->initSections( $post ) ) {
			add_meta_box(
                'the_permalinks_cascade',
                'The Permalinks Cascade',
                array( $this, 'displayMetaBox' ),
                $post_type,
                'side'
            );
        }
	}

    /**
     * @since 1.0
     * @param object $section
     */
    public function registerSection( $section ) {
        $this->sections[] = $section;
    }

    /**
     * @since 1.0
     *
     * @param object $post
     * @return bool
     */
    private function initSections( $post ) {
        if ( get_option( 'page_on_front' ) == $post->ID ) {
            return false;
        }

        if (
            $this->plugin->isSitemapActive( 'sitemap' ) &&
            ( 'page' == $post->post_type ) &&
            ( $post->ID != (int) $this->db->getOption( 'page_for_site_tree' ) ) &&
            ( $post->ID != get_option( 'page_for_posts' ) )
        ) {
            $ghost_section = new Section();
            $ghost_section->addField( new Field( 'is_ghost_page','MetaCheckbox', 'bool', '', 
                                                 __( 'This is a Ghost Page', 'the-permalinks-cascade' ), false ) );
            
            $this->registerSection( $ghost_section );
        }

        if ( 'page' == $post->post_type ) {
            $this->initTopicSection( $post );
        }
        
        $this->initExcludeFromSection( $post );
        
        return true;
    }

    /**
     * @since 1.0
     * @param object $post
     */
    private function initTopicSection( $post ) {
        $topic_tax_key = $this->db->prefixDBKey( 'topic' );
        $topic_section = new Section( __( 'Topic', 'the-permalinks-cascade' ), 'topic' );
        
        $query_args = array(
            'taxonomy'  => $topic_tax_key,
            'post_type' => 'page'
        );

        $url            = add_query_arg( $query_args, admin_url( 'edit-tags.php' ) );
        $new_topic_link = '<p><a href="' . $url . '">' . __( 'Create a new Topic', 'the-permalinks-cascade' ) . '</a></p>';

        $topic_section->setDescription( $new_topic_link );

        $topics = get_terms( array( 
            'taxonomy'   => $topic_tax_key,
            'hide_empty' => false
        ));

        if ( is_array( $topics ) ) {
            $page_topics = get_the_terms( $post, $topic_tax_key );
            $topic_id    = ( is_array( $page_topics ) ? (int) $page_topics[0]->term_id : 0 );

            $default_option_text = '&mdash; ' . __( 'Select', 'the-permalinks-cascade' ) . ' &mdash;';
            $options             = array( '0' => $default_option_text );

            foreach ( $topics as $topic ) {
                $id = (string) (int) $topic->term_id;
                $options[$id] = esc_attr( $topic->name );
            }

            $topic_section->addField( new Field( 'topic_id', 'Dropdown', 'choice', '', '', $topic_id, $options ) );
        }
        
        $this->registerSection( $topic_section );
    }

    /**
     * @since 1.0
     * @param object $post
     */
    private function initExcludeFromSection( $post ) {
        $exclude_section = new Section( __( 'Exclude From...', 'the-permalinks-cascade' ), 'exclude_from' );
        
        if (
            ( $post->ID != (int) $this->db->getOption( 'page_for_site_tree' ) ) &&
            $this->plugin->isSitemapActive( 'site_tree' ) &&
            $this->plugin->isContentTypeIncluded( $post->post_type, 'site_tree' )
        ) {
            $exclude_section->addField(
                new Field( 'exclude_from_site_tree','MetaCheckbox', 'bool', '', 'Site Tree', false, 'site_tree' )
            );
        }

        if ( 
            $this->plugin->isSitemapActive( 'sitemap' ) &&
            $this->plugin->isContentTypeIncluded( $post->post_type, 'sitemap' )
        ) {
            $exclude_section->addField(
                new Field( 'exclude_from_sitemap', 'MetaCheckbox', 'bool', '', 'Google Sitemap', false, 'sitemap' )
            );
        }

        if ( 
            $this->plugin->isSitemapActive( 'newsmap' ) &&
            $this->plugin->isContentTypeIncluded( $post->post_type, 'newsmap' )
        ) {
            $exclude_section->addField(
                new Field( 'exclude_from_newsmap', 'MetaCheckbox', 'bool', '', 'News Sitemap', false, 'newsmap' )
            );
        }

        $exclude_section->addField(
            new Field( 'exclude_from_hyperlists', 'MetaCheckbox', 'bool', '', __( 'All Hyper-lists', 'the-permalinks-cascade' ) )
        );
        
        $this->registerSection( $exclude_section );
    }

	/**
	 * @since 1.0
	 * @param object $post
	 */
	public function displayMetaBox( $post ) {
        $i = 0;

        echo '<input type="hidden" name="tpc_nonce" value="', wp_create_nonce( 'tpc_metadata' ), '">';
        
        foreach ( $this->sections as $section ) {
            $fields        = $section->fields();
            $section_title = $section->title();
            
            if ( $section_title ) {
                if ( $i > 0 ) {
                    echo '<h4 style="margin:30px 0 15px;">'; 
                }
                else {
                    echo '<h4 style="margin:20px 0 15px;">'; 
                } 

                echo esc_html( $section->title() ), '</h4>';  
            }

            echo wp_kses_data( $section->description() );
           
            foreach ( $fields as $field ) {
                $value = $this->db->getPostMeta( $post->ID,
                                                 $field->id(),
                                                 $field->defaultValue() );
                
                $filter = new OptionsFilter( $value, $field );
                $value  = $filter->filterOption();
                
                $fieldView = FieldView::makeView( $field );
                $fieldView->init( $value );

                if ( $section_title ) {
                    echo '<div style="margin-top:10px;">';  
                }
                else {
                    echo '<div style="margin-top:20px;">';
                }

                $fieldView->display();

                echo '</div>';
            }

            $i += 1;
        }
    }
	
	/**
	 * @since 1.0
	 *
	 * @param string $post_id
	 * @param object $post
	 */
	public function wpDidSavePost( $post_id, $post ) {
        if ( 
            !isset( $_POST['tpc_nonce'] ) || 
            ( 'auto-draft' == $post->post_status ) ||
            wp_is_post_revision( $post )
        ) {
            return false;
        }

        if (! check_admin_referer( 'tpc_metadata', 'tpc_nonce' ) ) {
            wp_die( 'You are being a bad fellow.' );
        }
            
        if (! current_user_can( 'edit_post', $post_id ) ) {
           wp_die( 'You are being a bad fellow.' );
        }
        
        if (! $this->initSections( $post ) ) {
           wp_die( 'You are being a bad fellow.' );
        }

        if ( isset( $_POST['tpc'] ) && !is_array( $_POST['tpc'] ) ) {
           wp_die( 'You are being a bad fellow.' );
        }

        $section_index = 0;
        $fields        = $this->sections[$section_index]->fields();

        if ( 'is_ghost_page' == $fields[$section_index]->id() ) {
            $section_index += 1;
            $is_ghost_page  = false;

            if ( isset( $_POST['tpc']['is_ghost_page'] ) ) {
                $is_ghost_page = sanitize_text_field( $_POST['tpc']['is_ghost_page'] );
                $filter        = new OptionsFilter( $is_ghost_page, $fields[0] );
                $is_ghost_page = $filter->filterOption();
            }

            if ( $is_ghost_page ) {
                $was_ghost_page = $this->db->getPostMeta( $post_id, 'is_ghost_page' );

                if (! $was_ghost_page ) {
                    $this->db->setPostMeta( $post_id, 'is_ghost_page', true );
                    $this->db->deletePostMeta( $post_id, 'exclude_from_sitemap' );
                    $this->db->deletePostMeta( $post_id, 'exclude_from_site_tree' );
                    $this->plugin->flushCachedData( 'sitemap' );
                    $this->plugin->flushCachedData( 'site_tree' );
                }
                
                return true;
            }
            
            $this->db->deletePostMeta( $post_id, 'is_ghost_page' );
        }

        while ( isset( $this->sections[$section_index] ) ) {
            $section    = $this->sections[$section_index];
            $section_id = $section->id();
            $fields     = $section->fields();

            foreach ( $fields as $field ) {
                $value    = false;
                $field_id = $field->id();
                
                if ( isset( $_POST['tpc'][$field_id] ) ) {
                    $value  = sanitize_text_field( $_POST['tpc'][$field_id] );
                    $filter = new OptionsFilter( $value, $field );
                    $value  = $filter->filterOption();
                }

                switch ( $section_id ) {
                    case 'exclude_from':
                         $this->processExcludeFlag( $post, $field, $value );
                         break;

                    case 'topic':
                        $this->processTopicID( $post, $field, $value );
                        break;

                    default:
                        /**
                         * @since 1.0
                         */
                        do_action( 'tpc_meta_box_controller_is_processing_data', $this, $post, $field, $value );
                        break;
                }
            }

            $section_index += 1;
        }

        return true;
    }

    /**
     * @since 1.0
     *
     * @param object $post
     * @param object $field
     * @param bool $exclude
     * @return bool
     */
    private function processExcludeFlag( $post, $field, $exclude ) {
        $field_id    = $field->id();
        $is_excluded = (bool) $this->db->getPostMeta( $post->ID, $field_id );

        if ( $exclude ) {
            if ( $is_excluded ) {
                return false;
            }

            $this->db->setPostMeta( $post->ID, $field_id, $exclude );
        }
        elseif ( $is_excluded ) {
            $this->db->deletePostMeta( $post->ID, $field_id );  
        }

        if ( ( $post->post_status != 'publish' ) || ( 'exclude_from_hyperlists' == $field_id ) ) {
            return false;
        }

        $context     = $field->additionalData();
        $is_new_post = ( strtotime( $post->post_modified ) - strtotime( $post->post_date ) < 5 );

        if ( !( $exclude && $is_new_post ) ) {
            $this->plugin->flushCachedData( $context );
        }
        
        if (
            !$exclude && 
            $is_new_post && 
            ( $context != 'site_tree' ) &&
            ( $this->db->getOption( 'automatic_pinging_on' ) || ( 'newsmap' == $context ) )
        ) {
            $this->plugin->invokeGlobalObject( 'PingController' )->ping( $context, $post );
        }

        return true;
    }

    /**
     * @since 1.0
     *
     * @param object $post
     * @param object $field
     * @param string $new_topic_id
     */
    private function processTopicID( $post, $field, $new_topic_id ) {
        $new_topic_id  = (int) $new_topic_id;
        $old_topic_id  = (int) $field->defaultValue();
        $topic_tax_key = $this->db->prefixDBKey( 'topic' );

        if ( ( $new_topic_id > 0 ) && ( $new_topic_id != $old_topic_id ) ) {
            wp_set_object_terms( $post->ID, $new_topic_id, $topic_tax_key );
        }
        elseif ( ( 0 == $new_topic_id ) && ( $old_topic_id > 0 ) ) {
            wp_remove_object_terms( $post->ID, $old_topic_id, $topic_tax_key  );
        }
    }
}