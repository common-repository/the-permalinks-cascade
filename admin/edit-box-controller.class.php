<?php
namespace ThePermalinksCascade;

/**
 * @package The Permalinks Cascade
 * @copyright Copyright 2022 Luigi Cavalieri.
 * @license https://opensource.org/licenses/GPL-3.0 GPL v3.0
 *
 * @since 1.0
 */
final class EditBoxController {
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
    private $sections;

    /**
     * @since 1.0
     * @var bool
     */
    private $isBulkEditUI = false;

    /**
     * @since 1.0
     * @var array
     */
    private $topicDropdownOptions;

    /**
     * @since 1.0
     * @param object $plugin
     */
    public function __construct( $plugin ) {
        $this->plugin = $plugin;
        $this->db     = $plugin->db();
    }

    /**
     * @since 1.0
     *
     * @param string $post_type
     * @return bool
     */
    public function initSections( $post_type ) {
        if ( 'page' == $post_type ) {
            $options = $this->getTopicDropdownOptions();

            if ( $options ) {
                if ( $this->isBulkEditUI ) {
                    $default_choice  = '0';
                    $default_options = array(
                        '0'  => $this->getDefaultDropdownLabel(),
                        '-1' => '[ ' . __( 'No Topic', 'the-permalinks-cascade' ) . ' ]'
                    );
                }
                else {
                    $default_choice  = '-1';
                    $default_options = array( '-1' => $this->getDefaultDropdownLabel() );  
                }

                $options = $default_options + $options;

                $topic_section = new Section();
                $topic_section->addField( new Field( 'topic_id', 'Dropdown', 'choice', 
                                                     __( 'Topic', 'the-permalinks-cascade' ), '', $default_choice, $options ) );

                $this->sections[0] = $topic_section;
            }

            if ( $this->isBulkEditUI ) {
                $options = array(
                    '0'  => $this->getDefaultDropdownLabel(),
                    '1'  => __( 'Yes', 'the-permalinks-cascade' ),
                    '-1' => __( 'No', 'the-permalinks-cascade' )
                );

                $ghost_page_section = new Section();
                $ghost_page_section->addField( new Field( 'is_ghost_page', 'Dropdown', 'choice', 
                                                          __( 'This is a Ghost Page', 'the-permalinks-cascade' ), '', '0', $options ) );

                $this->sections[1] = $ghost_page_section;
            }
        }
        
        if ( $this->isBulkEditUI ) {
            $exclude_section = new Section( __( 'Exclude From...', 'the-permalinks-cascade' ), 'exclude_from' );

            $options = array(
                '0' => $this->getDefaultDropdownLabel(),
                '1' => __( 'Exclude', 'the-permalinks-cascade' ),
                '2' => __( 'Include', 'the-permalinks-cascade' )
            );

            $sitemap_types_dictionary = array(
                'site_tree' => 'Site Tree',
                'sitemap'   => 'Google Sitemap',
                'newsmap'   => 'News Sitemap'
            );

            foreach ( $sitemap_types_dictionary as $sitemap_slug => $sitemap_name ) {
                if ( 
                    $this->plugin->isSitemapActive( $sitemap_slug ) && 
                    $this->plugin->isContentTypeIncluded( $post_type, $sitemap_slug ) 
                ) {
                    $exclude_section->addField( new Field( $sitemap_slug,'Dropdown', 'choice', $sitemap_name, '', '0', $options ) );
                } 
            }

            $exclude_section->addField(
                new Field( 'hyperlists', 'Dropdown', 'choice', 
                           __( 'All Hyper-lists', 'the-permalinks-cascade' ), '', '0', $options )
            );

            $this->sections[2] = $exclude_section; 
        }

        return (bool) $this->sections;
    }

    /**
     * @since 1.0
     * @return array
     */
    private function getTopicDropdownOptions() {
        if ( null === $this->topicDropdownOptions ) {
            $this->topicDropdownOptions = array();

            $topics = get_terms( array( 
                'taxonomy'   => $this->db->prefixDBKey( 'topic' ),
                'hide_empty' => false
            ));

            if ( $topics ) {
                foreach ( $topics as $topic ) {
                    $id = (string) (int) $topic->term_id;
                    $this->topicDropdownOptions[$id] = esc_attr( $topic->name );
                }   
            }
        }

        return $this->topicDropdownOptions;
    }

    /**
     * @since 1.0
     * @return string
     */
    private function getDefaultDropdownLabel() {
        if ( $this->isBulkEditUI ) {
            return ( '&mdash; ' . __( 'No Change', 'the-permalinks-cascade' ) . ' &mdash;' );
        }

        return ( '&mdash; ' . __( 'No Topic', 'the-permalinks-cascade' ) . ' &mdash;' );
    }

    /**
     * @since 1.0
     *
     * @param string $column_name
     * @param string $post_type
     */
    public function displayBulkEditBox( $column_name, $post_type ) {
        $this->isBulkEditUI = true;

        $this->displayBox( $column_name, $post_type );
    }

    /**
     * @since 1.0
     *
     * @param string $column_name
     * @param string $post_type
     */
    public function displayQuickEditBox( $column_name, $post_type ) {
        $this->displayBox( $column_name, $post_type );
    }

    /**
     * @since 1.0
     *
     * @param string $column_name
     * @param string $post_type
     * @return bool
     */
    public function displayBox( $column_name, $post_type ) {
        $topic_col_name = 'taxonomy-' . $this->db->prefixDBKey( 'topic' );

        if ( ( $column_name != 'tpc_exclusions' ) && ( $column_name != $topic_col_name ) )  {
            return false;
        }

        if (! $this->initSections( $post_type ) ) {
            return false;
        }

        echo '<fieldset ';   

        if ( $this->isBulkEditUI ) {
            $nonce_key = 'tpc_bulk_edit';

            echo 'id="tpc-bulk-edit-box" ';

            remove_action( 'bulk_edit_custom_box', array( $this, 'displayBulkEditBox' ), 100 );
        }
        else {
            $nonce_key = 'assign_topic';

            remove_action( 'quick_edit_custom_box', array( $this, 'displayQuickEditBox' ), 100 );
        }

        echo 'class="inline-edit-col-right"><div class="inline-edit-col"><input type="hidden" id="tpc-nonce" name="tpc_nonce" value="',
             wp_create_nonce( $nonce_key ), '">';

        foreach ( $this->sections as $section ) {
            $section_id = $section->id();
            $title      = $section->title();
            $fields     = $section->fields();

            if ( $title ) {
                echo '<h4>', esc_html( $title ), '</h4>';
            }

            foreach ( $fields as $field ) {
                $fieldView = FieldView::makeView( $field );
                $fieldView->init( $field->defaultValue(), $section_id );

                echo '<label class="inline-edit-group"><span class="title';

                if ( $section_id ) {
                    echo ' tpc-', esc_attr( $section_id ), '-control-title';
                }
                else {
                    echo ' tpc-control-title';
                }
                
                echo '">', esc_html( $field->title() ), '</span>';

                $fieldView->display();
        
                echo '</label>';
            }
        }
        
        echo '</div></fieldset>';

        return true;
    }

    /**
     * @since 1.0
     *
     * @param array $data
     * @return bool
     */
    public function processBulkEditAction( &$data ) {
        $do_assign_topic  = ( isset( $data['topic_id'] ) && ( $data['topic_id'] !== 0 ) );
        $do_is_ghost_page = ( isset( $data['is_ghost_page'] ) && ( $data['is_ghost_page'] !== 0 ) );
        $do_exclude_from  = ( isset( $data['exclude_from'] ) && is_array( $data['exclude_from'] ) );
        
        if ( !( $do_assign_topic || $do_is_ghost_page || $do_exclude_from ) ) {
            return false;
        }

        $new_db_rows = $page_ids_list = $meta_key = '';
        $force_reset_exclusions = false;

        if ( $do_is_ghost_page ) {
            $force_reset_exclusions = ( $data['is_ghost_page'] === 1 );
            $is_ghost_page_meta_key = $this->db->prepareMetaKey( 'is_ghost_page' );
        }

        foreach ( $_POST['post_ids'] as $page_id ) {
            $page_id = (int) $page_id;

            if ( $page_id < 0 ) {
                continue;
            }

            if ( $do_assign_topic ) {
                $this->assignTopicToPage( $data['topic_id'], $page_id ); 
            }

            if ( $do_is_ghost_page ) {
                switch ( $data['is_ghost_page'] ) {
                    case 1:
                        if (! $this->db->getPostMeta( $page_id, 'is_ghost_page' ) ) {
                            $new_db_rows .= '(' . $page_id . ',' . $is_ghost_page_meta_key . ',1),';
                        }
                        break;

                    case -1:
                        $page_ids_list .= $page_id . ',';
                        break;
                }
                
            }
            elseif ( $this->db->getPostMeta( $page_id, 'is_ghost_page' ) ) {
                continue;
            }

            if ( $do_exclude_from ) {
                foreach ( $data['exclude_from'] as $resource_type_slug => $code ) {
                    if ( $force_reset_exclusions ) {
                        $code = -1;
                    }
                    elseif ( 0 === $code ) {
                        continue;
                    }

                    $this->processExcludeAction( $page_id, $code, $resource_type_slug );
                }
            }
        }

        if ( $new_db_rows ) {
            $this->updatePostmetaTableOnDoingGhostPageAction( 
                $new_db_rows, 
                'INSERT INTO %1$s ( post_id, meta_key, meta_value ) VALUES %2$s'
            );
        }
        elseif ( $page_ids_list ) {
            $query_string = 'DELETE FROM %1$s WHERE meta_key = ' . $is_ghost_page_meta_key . ' AND post_id IN (%2$s)';

            $this->updatePostmetaTableOnDoingGhostPageAction( $page_ids_list, $query_string );
        }

        return true;
    }

    /**
     * @since 1.0
     *
     * @param int $new_topic_id
     * @param object|int $page_or_id
     */
    public function assignTopicToPage( $new_topic_id, $page_or_id ) {
        $topic_tax_key = $this->db->prefixDBKey( 'topic' );
        $page_id       = ( is_object( $page_or_id ) ? $page_or_id->ID : $page_or_id );
        $page_topics   = get_the_terms( $page_or_id, $topic_tax_key );
        $old_topic_id  = ( is_array( $page_topics ) ? (int) $page_topics[0]->term_id : 0 );

        if ( ( $new_topic_id > 0 ) && ( $new_topic_id != $old_topic_id ) ) {
            wp_set_object_terms( $page_id, $new_topic_id, $topic_tax_key );
        }
        elseif ( ( -1 == $new_topic_id ) && ( $old_topic_id > 0 ) ) {
            wp_remove_object_terms( $page_id, $old_topic_id, $topic_tax_key  );
        }
    }

    /**
     * @since 1.0
     *
     * @param int $post_id
     * @param int $code
     * @param string $resource_type_slug
     * @return bool
     */
    private function processExcludeAction( $post_id, $code, $resource_type_slug ) {
        $post_meta_key = 'exclude_from_' . $resource_type_slug;
        $exclude       = ( $code === 1 );
        $is_excluded   = (bool) $this->db->getPostMeta( $post_id, $post_meta_key );

        if ( $exclude ) {
            if ( $is_excluded ) {
                return true;
            }

            $this->db->setPostMeta( $post_id, $post_meta_key, $exclude );
        }
        elseif ( $is_excluded ) {
            $this->db->deletePostMeta( $post_id, $post_meta_key );  
        }

        return true;
    }

    /**
     * @since 1.0
     *
     * @param string $data_string
     * @param string $query_string
     */
    private function updatePostmetaTableOnDoingGhostPageAction( $data_string, $query_string ) {
        global $wpdb;

        // Removes the trailing comma from the string.
        $data_string = rtrim( $data_string, ' ,' );

        $wpdb->query( sprintf( $query_string, $wpdb->postmeta, $data_string ) );
    }
}