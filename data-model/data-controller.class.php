<?php
namespace ThePermalinksCascade;

/**
 * @package The Permalinks Cascade
 * @copyright Copyright 2022 Luigi Cavalieri.
 * @license https://opensource.org/licenses/GPL-3.0 GPL v3.0
 *
 * @since 1.0
 */
class DataController {
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
    private $pages = array();

    /**
     * @since 1.0
     * @var array
     */
    private $sections = array();

    /**
     * @since 1.0
     * @var string
     */
    private $currentlyLoadingDataID = '';
    
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
     * @return string
     */
    public function getCurrentlyLoadingDataID() {
        return $this->currentlyLoadingDataID;
    }
    
    /**
     * @since 1.0
     *
     * @param bool $include_non_active
     * @return array
     */
    public function pages( $include_non_active = true ) {
        if (! $this->pages ) {
            $pages = array();

            Page::setNamespace( 'tpc' );

            $pages[] = new Page( 'dashboard', 'tpc-dashboard', __( 'Dashboard', 'the-permalinks-cascade' ),
                                 __( 'Dashboard', 'the-permalinks-cascade' ), 'DashboardPageView', 'DashboardController' );
            
            if ( $include_non_active || $this->plugin->isSitemapActive( 'site_tree' ) ) {
                $pages[] = new Page( 'site_tree', 'tpc-dashboard', __( 'Site Tree Settings', 'the-permalinks-cascade' ), 
                                     __( 'Site Tree Settings', 'the-permalinks-cascade' ), 'PageView', 'PageController' );
            }

            $pages[] = new Page( 'advanced', 'tpc-dashboard', __( 'Advanced Settings', 'the-permalinks-cascade' ), 
                                 __( 'Advanced Settings', 'the-permalinks-cascade' ), 'PageView', 'PageController' );

            if (! $include_non_active ) {
                return $pages;
            }

            $this->pages = $pages;
        }

        return $this->pages;
    }
    
    /**
     * @since 1.0
     *
     * @param string $id
     * @return object|bool
     */
    public function page( $id ) {
        $pages = $this->pages();
        
        foreach ( $pages as $page ) {
            if ( $page->id() == $id )
                return $page;
        }

        return false;
    }
    
    /**
     * @since 1.0
     *
     * @param string $page_id
     * @param bool $load_all_sections
     */
    public function loadPageSections( $page_id, $load_all_sections = false ) {
        if (! isset( $this->sections[$page_id] ) ) {
            $this->currentlyLoadingDataID = $page_id;

            $page = $this->page( $page_id );

            if ( $page ) {
                $data_file_name = $page_id . '-page-data.php';

                if ( WP_DEBUG ) {
                    include( $data_file_name );
                }
                else {
                    @include( $data_file_name );
                }

                /**
                 * @since 1.0
                 */
                do_action( 'tpc_data_controller_did_load_sections', $this );
            }
        }

        if ( $load_all_sections || 'site_tree' != $page_id ) {
            return $this->sections[$page_id];
        }

        $sections = array();

        foreach ( $this->sections['site_tree'] as $section ) {
            $section_id = $section->id();

            if ( $this->plugin->isContentTypeIncluded( $section_id, 'site_tree' ) ) {
                $sections[$section_id] = $section;
            }
        }

        return $sections;
    }

    /**
     * @since 1.0
     *
     * @param object $section
     * @param string $page_id
     */
    public function registerSection( $section, $page_id = '' ) {
        if ( '' === $page_id ) {
            $page_id = $this->currentlyLoadingDataID;
        }

        $section_id = $section->id();

        if ( $section_id ) {
            $this->sections[$page_id][$section_id] = $section;
        }
        else {
            $this->sections[$page_id][] = $section;
        }
    }

    /**
     * @since 1.0
     *
     * @param string $section_id
     * @param string $page_id
     * @return array
     */
    public function getSections( $section_id = '', $page_id = ''  ) {
        if ( '' === $page_id ) {
            $page_id = $this->currentlyLoadingDataID;
        }

        if ( $section_id ) {
            if ( isset( $this->sections[$page_id][$section_id] ) ) {
                return array( $section_id => $this->sections[$page_id][$section_id] );
            }
        }
        elseif ( isset( $this->sections[$page_id] ) ) {
            return $this->sections[$page_id];
        }

        return array();
    }

    /**
     * @since 1.0
     *
     * @param object $section
     * @param string $page_id
     * @return bool
     */
    public function stackUpSection( $section, $page_id = ''  ) {
        if ( '' === $page_id ) {
            $page_id = $this->currentlyLoadingDataID;
        }

        if ( !( is_object( $section ) && isset( $this->sections[$page_id] ) ) ) {
            return false;
        }

        $section_id = $section->id();

        if ( $section_id ) {
            $this->sections[$page_id] = array( $section_id => $section ) + $this->sections[$page_id];
        }
        else {
            $this->sections[$page_id] = array_merge( array( $section ), $this->sections[$page_id] );
        }
        
        return true;
    }

    /**
     * @version 4.3
     *
     * @param array $options
     * @param array $defaults
     * @return array
     */
    private function fillOptionsArrayWithDefaults( &$options, $defaults ) {
        foreach( $defaults as $key => $value ) {
            if ( is_array( $value ) ) {
                if (! isset( $options[$key] ) ) {
                    $options[$key] = array();
                }

                $this->fillOptionsArrayWithDefaults( $options[$key], $value );
            }
            elseif (! isset( $options[$key] ) ) {
                $options[$key] = ( is_bool( $value ) ? false : $value );
            }
        }
    }

    /**
     * @since 2.0.1
     *
     * @param array $options
     * @param object $page
     * @param string $dashform_id
     * @return array
     */
    public function &validateOptions( &$options, $page, $dashform_id = '' ) {
        $validated_options = array();
        $page_id           = $page->id();
        
        $this->loadPageSections( $page_id );

        $sections = $this->getSections( $dashform_id );

        foreach ( $sections as $section ) {
            $section_id   = $section->id();
            $section_data = isset( $options[$section_id] ) ? $options[$section_id] : $options;

            if ( 'site_tree' == $page_id ) {
                $validated_options[$page_id][$section_id] = $this->validationCallback( $section, $section_data );
            }
            elseif ( $section_id ) {
                $validated_options[$section_id] = $this->validationCallback( $section, $section_data );
            }
            else {
                $validated_options += $this->validationCallback( $section, $section_data );
            }
        }

        $this->fillOptionsArrayWithDefaults( $validated_options, $this->defaultsForPage( $page_id, $dashform_id ) );

        return $validated_options;
    }
    
    /**
     * @since 2.0.1
     *
     * @param object $section
     * @param array $options
     */
    private function &validationCallback( $section, $options ) {
        $options = (array) $options;

        foreach ( $options as $option_id => $option_value ) {
            $field = $section->getField( $option_id );

            if ( $field ) {
                if ( $field instanceof Fieldset ) {
                    $fieldset            = $field;
                    $fieldset_data       = &$option_value;
                    $options[$option_id] = $this->validationCallback( $fieldset, $fieldset_data );
                }
                else {
                    $filter = new OptionsFilter( $option_value, $field );

                    /**
                     * @since 1.0
                     */
                    $options[$option_id] = apply_filters( 'tpc_data_controller_sanitised_option_value', 
                                                          $filter->filterOption(), $option_id, $section );
                }                
            }
            else {
                unset( $options[$option_id] );
            }
        }

        return $options;
    }

    /**
     * @since 1.0
     *
     * @param string $page_id
     * @param string $dashform_id
     * @param bool $load_all_sections
     * @return array
     */
    public function &defaultsForPage( $page_id, $dashform_id = '', $load_all_sections = false ) {
        $this->loadPageSections( $page_id, $load_all_sections );

        $defaults = array();
        $sections = $this->getSections( $dashform_id, $page_id );

        foreach ( $sections as $section ) {
            $section_id = $section->id();

            if ( 'site_tree' == $page_id ) {
                $defaults[$page_id][$section_id] = $this->defaultsCallback( $section ); 
            }
            elseif ( $section_id ) {
                $defaults[$section_id] = $this->defaultsCallback( $section ); 
            }
            else {
                $defaults += $this->defaultsCallback( $section ); 
            }
        }

        return $defaults;
    }

    /**
     * @since 1.0
     *
     * @param object $section
     * @return array
     */
    private function defaultsCallback( $section ) {
        $defaults = array();
        $fields   = $section->getFieldsFromDictionary();

        foreach ( $fields as $field_id => $field ) {
            if ( $field instanceof Fieldset ) {
                $defaults[$field_id] = $this->defaultsCallback( $field );
            }
            else {
                $defaults[$field_id] = $field->defaultValue(); 
            }
        }

        return $defaults;
    }
}