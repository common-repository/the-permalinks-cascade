<?php
namespace ThePermalinksCascade;

/**
 * @package The Permalinks Cascade
 * @copyright Copyright 2022 Luigi Cavalieri.
 * @license https://opensource.org/licenses/GPL-3.0 GPL v3.0
 *
 * @since 2.0
 */
final class HyperlistController {
    /**
     * @since 2.0
     * @var object
     */
    private $plugin;

    /**
     * @since 2.0
     * @var array
     */
    private $contentTypes;

    /**
     * @since 2.0
     * @var array
     */
    private $postTypes;

    /**
     * @since 2.0
     * @var array
     */
    private $defaults;

    /**
     * @since 2.0
     * @var bool
     */
    private $blockTypes;

    /**
     * @since 2.0
     * @var bool
     */
    private $doingBlock = false;

    /**
     * @since 2.0
     * @var bool
     */
    private $doingShortcode = false;

    /**
     * @since 2.0
     * @param object $plugin
     */
    public function __construct( $plugin ) {
        $this->plugin = $plugin;
    }

    /**
     * Registers the Block Types.
     * 
     * @since 2.0
     */
    public function wpDidFinishLoading() {
        $this->loadContentTypes();
        $this->populateBlockTypesArray();

        foreach ( $this->blockTypes as $id => $title ) {
            register_block_type_from_metadata( $this->plugin->dirPath( "blocks/{$id}-block.json" ), array(
                'title'           => $title,
                'render_callback' => array( $this, str_replace( '-', '_', "doBlock_{$id}" ) )
            ));
        }

        if (! is_admin() ) {
            return null;
        }

        add_filter( 'block_categories_all', function( $categories ) {
            $tpc_category = array(
                'slug'  => 'the-permalinks-cascade',
                'title' => 'The Permalinks Cascade'
            );

            return array_merge( $categories, array( $tpc_category ) );
        });

        add_action( 'admin_print_scripts', function() {
            echo "\n<script>const TPC_Globals = { showCustomPostBlock: ";
            echo ( isset( $this->blockTypes['custom-post'] ) ? 'true' : 'false' ); 
            echo " };</script>\n";
        });
    }

    /**
     * @since 2.0
     *
     * @param array $attributes
     * @param string $the_content
     * @return string
     */
    public function doBlock_page( $attributes, $the_content ) {
        $this->doingBlock = true;

        return $this->getHyperlist( 'page', $attributes );
    }

    /**
     * @since 2.0
     *
     * @param array $attributes
     * @param string $the_content
     * @return string
     */
    public function doBlock_post( $attributes, $the_content ) {
        $this->doingBlock = true;

        return $this->getHyperlist( 'post', $attributes );
    }

    /**
     * @since 2.0
     *
     * @param array $attributes
     * @param string $the_content
     * @return string
     */
    public function doBlock_taxonomy( $attributes, $the_content ) {
        if ( isset( $attributes['taxonomy_slug'] ) ) {
            $this->doingBlock = true;

            return $this->getHyperlist( $attributes['taxonomy_slug'], $attributes );
        }

        return '';
    }

    /**
     * @since 2.0
     *
     * @param array $attributes
     * @param string $the_content
     * @return string
     */
    public function doBlock_custom_post( $attributes, $the_content ) {
        if ( isset( $attributes['post_slug'] ) && 'none' != $attributes['post_slug'] ) {
            $this->doingBlock = true;

            return $this->getHyperlist( $attributes['post_slug'], $attributes );
        }

        return '';
    }


    /**
     * @since 2.1
     */
    private function populateBlockTypesArray() {
        $this->blockTypes = array(
            'page' => __( 'Pages', 'the-permalinks-cascade' ),
            'post' => __( 'Posts', 'the-permalinks-cascade' )
        );

        if ( count( $this->postTypes ) > 2 ) {
            $this->blockTypes['custom-post'] = __( 'Custom Posts', 'the-permalinks-cascade' );
        }

        $this->blockTypes['taxonomy'] = __( 'Terms', 'the-permalinks-cascade' );
    }

    /**
     * @since 2.0
     *
     * @param array $attributes
     * @return string
     */
    public function doShortcode( $attributes ) {
        if (! isset( $attributes['type'] ) ) {
            return '';
        }

        $this->doingShortcode = true;
        
        return $this->getHyperlist( $attributes['type'], $attributes );
    }

    /**
     * @since 2.0
     *
     * @param string $type
     * @param array $arguments
     * @return string
     */
    public function getHyperlist( $type, $arguments = array() ) {
        $type = sanitize_key( $type );

        $this->loadContentTypes();

        if (! isset( $this->contentTypes[$type] ) ) {
            return '';
        }

        $this->loadDefaults();

        $content_type = $this->contentTypes[$type];

        if ( $this->doingShortcode ) {
            $list_options = shortcode_atts( $this->defaults[$content_type], $arguments, 'permalinks-cascade' );   
        }
        else {
            $list_options = wp_parse_args( $arguments, $this->defaults[$content_type] );
        }

        if ( 'page' == $content_type ) {
            if ( 
                $this->doingShortcode && 
                isset( $list_options['only_children_of'] ) && 
                ( 'this' == $list_options['only_children_of'] )
            ) {
                global $post;

                if ( 'page' == $post->post_type ) {
                    $list_options['only_children_of'] = $post->ID;
                }
            }  
        }

        $builder = $this->plugin->invokeGlobalObject( 'SiteTreeBuilder' );
        $builder->setDoingHyperlist( true );
        $builder->setDoingBlock( $this->doingBlock );
        $builder->setDoingShortcode( $this->doingShortcode );

        return $builder->buildList( $content_type, $list_options );
    }

    /**
     * @since 2.0
     */
    private function loadContentTypes() {
        if ( $this->contentTypes ) {
            return null;
        }

        $this->contentTypes = array(
            'post'     => 'post',
            'page'     => 'page',
            'category' => 'category',
            'post_tag' => 'post_tag',
            'author'   => 'authors'
        );

        $this->postTypes = array(
            'post' => 'post',
            'page' => 'page'
        );

        $post_types = get_post_types( array( 'public' => true, '_builtin' => false ) );
        $taxonomies = get_taxonomies( array( 'public' => true, '_builtin' => false ) );

        foreach ( $post_types as $post_type ) {
            $this->postTypes[$post_type]    = $post_type;
            $this->contentTypes[$post_type] = $post_type;
        }

        foreach ( $taxonomies as $taxonomy ) {
            $this->contentTypes[$taxonomy] = $taxonomy;
        }
    }

    /**
     * @since 2.0
     */
    private function loadDefaults() {
        if ( $this->defaults ) {
            return null;
        }

        $defaults_for_page = $this->plugin->invokeGlobalObject( 'DataController' )->defaultsForPage( 'site_tree', '', true );

        $this->defaults = &$defaults_for_page['site_tree'];

        foreach ( $this->contentTypes as $content_type ) {
            $this->defaults[$content_type]['show_title'] = true;

            if ( isset( $this->postTypes[$content_type] ) ) {
                $this->defaults[$content_type]['exclude']      = '';
                $this->defaults[$content_type]['include_only'] = '';

                if ( $this->doingShortcode ) {
                    $this->defaults[$content_type]['include_globally_excluded'] = false;
                }
            }

            if (! isset( $this->defaults[$content_type]['limit'] ) ) {
                $this->defaults[$content_type]['limit'] = 100;
            }
        }

        $this->defaults['page']['only_children_of'] = 0;
    }
}