<?php
namespace ThePermalinksCascade;

/**
 * @package The Permalinks Cascade
 * @copyright Copyright 2022 Luigi Cavalieri.
 * @license https://opensource.org/licenses/GPL-3.0 GPL v3.0
 *
 * @since 2.0
 */
final class WPMLModule {
    /**
     * @since 2.0
     * @var object
     */
    private static $singleton;

    /**
     * @since 2.0
     * @var object
     */
    private $plugin;

    /**
     * @since 2.0
     * @var object
     */
    private $db;

    /**
     * @since 2.0
     * @var string
     */
    private $requestedSitemapSlug = '';

    /**
     * @since 2.0
     * @var string
     */
    private $siteDefaultLanguage;

    /**
     * @since 2.0
     * @var array
     */
    private $trData = array(
        'post'      => array(),
        'taxonomy' => array()
    );

    /**
     * @since 2.0
     * @var array
     */
    private $trObjects = array();

    /**
     * @since 2.0
     * @var string
     */
    private $iclPostType;

    /**
     * @since 2.0
     * @var string
     */
    private $iclTaxonomy;

    /**
     * Possible values: post, taxonomy.
     *
     * @since 2.0
     * @var string
     */
    private $currentContentTypeFamily;

    /**
     * @since 2.0
     * @var string
     */
    private $lineBreak = '';

    /**
     * @since 2.0
     * 
     * @param object $plugin
     * @return bool
     */
    public static function launch( $plugin ) {
        if ( self::$singleton ) {
            return false;
        }

        self::$singleton = new self( $plugin );
        self::$singleton->registerMainHooks();

        return true;
    }

    /**
     * @since 2.0
     * @param object $plugin
     */
    private function __construct( $plugin ) {
        $this->plugin = $plugin;
        $this->db     = $plugin->db();
    }

    /**
     * @since 2.0
     * @return bool
     */
    private function registerMainHooks() {
        if ( is_admin() ) {
            add_action( 'wp_loaded', array( $this, 'wpAdminDidFinishLoading' ) );

            return true;
        }

        if ( $this->plugin->isSitemapActive( 'site_tree' ) ) {
            add_filter( 'tpc_can_filter_page_content', array( $this, 'tpcCanFilterPageContent' ), 10, 3 );
            add_action( 'tpc_will_build_single_list', array( $this, 'tpcWillBuildSingleList' ) );
            add_action( 'tpc_did_build_single_list', array( $this, 'tpcDidBuildSingleList' ) );
        }

        if ( $this->plugin->isSitemapActive( 'sitemap' ) || $this->plugin->isSitemapActive( 'newsmap' ) ) {
            add_action( 'tpc_is_processing_sitemap_request', array( $this, 'tpcIsProcessingSitemapRequest' ) );
        }

        return true;
    }

    /**
     * @since 2.0
     */
    public function wpAdminDidFinishLoading() {
        global $pagenow;

        switch ( $pagenow ) {
            case 'post.php':
            case 'post-new.php':
                add_filter( 'tpc_ping_controller_can_ping', array( $this, 'tpcPingControllerCanPing' ), 10, 3 );
                break;
            default:
                add_filter( 'tpc_dashboard_page_data_pages_dropdown_query', 
                            array( $this, 'tpcDashboardPageDataPagesDropdownQuery' ) );
                add_filter( 'tpc_data_controller_sanitised_option_value',
                            array( $this, 'tpcDataControllerSanitisedOptionValue' ), 10, 3 );
        }
    }

    /**
     * @since 2.0
     *
     * @param bool $can_ping
     * @param string $sitemap_slug
     * @param object $post
     * @return bool
     */
    public function tpcPingControllerCanPing( $can_ping, $sitemap_slug, $post ) {
        // Is it an automatic ping?
        if ( is_object( $post ) ) {
            $can_ping = false;

            switch ( $sitemap_slug ) {
                case 'sitemap':
                    if ( !isset( $_POST['icl_trid'] ) || ( 0 == (int) $_POST['icl_trid'] ) ) {
                        return true;
                    }
                    break;

                case 'newsmap':
                    global $sitepress;

                    if ( ICL_LANGUAGE_CODE == $sitepress->get_default_language() ) {
                        return true;
                    }
                    break;
            }
        }

        return $can_ping;
    }

    /**
     * @since 2.0
     *
     * @param array $the_query
     * @return array
     */
    public function tpcDashboardPageDataPagesDropdownQuery( $the_query ) {
        global $wpdb, $sitepress;

        $the_query['FROM']  .= " INNER JOIN {$wpdb->prefix}icl_translations AS iclt 
                                   ON iclt.element_id = p.ID AND iclt.element_type = 'post_page'";
        $the_query['WHERE'] .= " AND iclt.language_code = '{$sitepress->get_default_language()}'";

        $page_on_front  = (int) get_option( 'page_on_front' );
        $page_for_posts = (int) get_option( 'page_for_posts' );

        if ( $page_on_front || $page_for_posts ) {
            $the_query['WHERE'] .= " AND iclt.trid NOT IN (
                                        SELECT iclt2.trid
                                        FROM {$wpdb->prefix}icl_translations AS iclt2
                                        WHERE iclt2.element_id IN ({$page_on_front},{$page_for_posts})
                                     )";
        }

        return $the_query;
    }

    /**
     * @since 2.0
     *
     * @param mixed $value
     * @param string $option_id
     * @param object $section
     * @return mixed
     */
    public function tpcDataControllerSanitisedOptionValue( $value, $option_id, $section ) {
        if ( $value && ( 'title' == $option_id ) ) {
            $content_type_id = $section->id();
            
            do_action( 'wpml_register_single_string', 'The Permalinks Cascade', "Hyperlist Title ({$content_type_id})", $value );
        }

        return $value;
    }

    /**
     * @since 2.0
     *
     * @param bool $is_site_tree_page
     * @param int $page_for_site_tree
     * @param int $requested_page_id
     * @return bool
     */
    public function tpcCanFilterPageContent( $is_site_tree_page, $page_for_site_tree, $requested_page_id ) {
        $can_filter = $is_site_tree_page;

        if (! $is_site_tree_page ) {
            global $sitepress;

            $original_lang_page_id = (int) apply_filters( 'wpml_object_id', 
                                                          $requested_page_id, 'page', FALSE, $sitepress->get_default_language() );

            $can_filter = ( $original_lang_page_id === $page_for_site_tree );

            if ( $can_filter ) {
                add_action( 'tpc_will_build_site_tree', function( $builder ) {
                    add_filter( 'tpc_builder_hyperlist_title', array( $this, 'tpcBuilderHyperlistTitle' ), 10, 2 );
                });
            }
        }

        if ( $can_filter ) {
            add_action( 'tpc_paginator_will_build_index', array( $this, 'tpcPaginatorWillBuildIndex' ) );
        }
        
        return $can_filter;
    }

    /**
     * @since 2.0
     * @param string $the_title
     * @param object $builder
     * @return string
     */
    public function tpcBuilderHyperlistTitle( $the_title, $builder ) {
        return apply_filters( 'wpml_translate_single_string', $the_title,
                              'The Permalinks Cascade', "Hyperlist Title ({$builder->listID()})" );
    }

    /**
     * @since 2.0
     * @param object $paginator
     */
    public function tpcPaginatorWillBuildIndex( $paginator ) {
        add_filter( 'tpc_paginator_posts_count_query', array( $this, 'filterMainPostsQuery'), 10, 2 );
        add_filter( 'tpc_paginator_taxonomies_count_query', array( $this, 'filterMainTaxonomiesQuery'), 10, 2 );
    }

    /**
     * @since 2.0
     *
     * @param array $query_clauses
     * @param string $post_types_list
     */
    public function filterMainPostsQuery( $query_clauses, $post_types_list ) {
        global $wpdb;

        $post_types_list = "'post_" . trim( str_replace( ",'", ",'post_", $post_types_list ), "'" ) . "'";

        $query_clauses['FROM'] .= " INNER JOIN {$wpdb->prefix}icl_translations AS iclt 
                                    ON iclt.element_id = p.ID AND iclt.element_type IN ({$post_types_list})";
        
        switch ( $this->requestedSitemapSlug ) {
            case 'sitemap':
                $query_clauses['WHERE'] .= ' AND iclt.source_language_code IS NULL';
                break;

            case 'newsmap':
                $query_clauses['WHERE'] .= " AND iclt.language_code = '{$this->siteDefaultLanguage}'";
                break;

            default:
                $query_clauses['WHERE'] .= " AND iclt.language_code = '" . ICL_LANGUAGE_CODE . "'";
                break;
        }
        
        $this->iclPostType = $post_types_list;

        return $query_clauses;
    }

    /**
     * @since 2.0
     * 
     * @param array $query_clauses
     * @param string $taxonomies_list
     * @return array
     */
    public function filterMainTaxonomiesQuery( $query_clauses, $taxonomies_list ) {
        global $wpdb;

        $taxonomies_list = "'tax_" . trim( str_replace( ",'", ",'tax_", $taxonomies_list ), "'" ) . "'";

        $query_clauses['INNER_JOIN'] .= " LEFT OUTER JOIN {$wpdb->prefix}icl_translations AS iclt 
                                          ON iclt.element_id = t.term_id AND iclt.element_type IN ({$taxonomies_list})";
        
        switch ( $this->requestedSitemapSlug ) {
            case 'sitemap':
                $query_clauses['WHERE'] .= ' AND iclt.source_language_code IS NULL';
                break;

            default:
                $query_clauses['WHERE'] .= " AND iclt.language_code = '" . ICL_LANGUAGE_CODE . "'";
                break;
        }

        $this->iclTaxonomy = $taxonomies_list;

        return $query_clauses;
    }

    /**
     * @since 2.0
     * @param object $builder
     */
    public function tpcWillBuildSingleList( $builder ) {
        global $wpml_url_filters;
            
        $wpml_url_filters->remove_global_hooks();

        $filter_get_permalink_callback = array( $this, 'filterHyperlistGetPermalink' );

        add_filter( 'post_link', $filter_get_permalink_callback );
        add_filter( 'post_type_link', $filter_get_permalink_callback );

        if ( 'page' == $builder->listID() ) {
            add_filter( 'home_url', $filter_get_permalink_callback );   
        }
        
        if (! has_action( 'tpc_builder_will_query_db', array( $this, 'tpcBuilderWillQueryDB' ) ) ) {
            add_action( 'tpc_builder_will_query_db', array( $this, 'tpcBuilderWillQueryDB' ) );  
        }
    }

    /**
     * @since 2.0
     * @param string $permalink
     */
    public function filterHyperlistGetPermalink( $permalink ) {
        return apply_filters( 'wpml_permalink', $permalink );
    }

    /**
     * @since 2.0
     * @param object $builder
     */
    public function tpcBuilderWillQueryDB( $builder ) {
        if ( 'post' == $builder->getContentTypeFamily() ) {
            global $wpdb;

            $post_type             = $builder->listID();
            $current_language_code = ICL_LANGUAGE_CODE;

            $builder->prependToQueryClause( 'joins', "INNER JOIN {$wpdb->prefix}icl_translations AS iclt 
                                                      ON iclt.element_id = p.ID AND iclt.element_type = 'post_{$post_type}'" );
            $builder->appendToQueryClause( 'where', "AND iclt.language_code = '{$current_language_code}'" );
        }
    }

    /**
     * @since 2.0
     * @param object $builder
     */
    public function tpcDidBuildSingleList( $builder ) {
        global $wpml_url_filters;

        $filter_get_permalink_callback = array( $this, 'filterHyperlistGetPermalink' );

        remove_filter( 'post_link', $filter_get_permalink_callback );
        remove_filter( 'post_type_link', $filter_get_permalink_callback );

        if ( 'page' == $builder->listID() ) {
            remove_filter( 'home_url', $filter_get_permalink_callback );   
        }
            
        $wpml_url_filters->add_global_hooks();
    }

    /**
     * @since 2.0
     * @param string $sitemap_slug
     */
    public function tpcIsProcessingSitemapRequest( $sitemap_slug ) {
        add_action( 'tpc_indexer_will_build_index', array( $this, 'tpcIndexerWillBuildIndex' ) );
        add_action( 'tpc_will_serve_sitemap', array( $this, 'tpcWillServeSitemap' ) );

        if ( 'sitemap' == $sitemap_slug ) {
            add_action( 'tpc_will_serve_stylesheet', array( $this, 'tpcWillServeStylesheet' ) );    
        }
    }

    /**
     * @since 2.0
     * @param object $indexer
     */
    public function tpcIndexerWillBuildIndex( $indexer ) {
        global $sitepress;

        $this->requestedSitemapSlug = $indexer->getRequestedSitemapSlug();
        $this->siteDefaultLanguage  = $sitepress->get_default_language();

        $filter_main_posts_query_callback = array( $this, 'filterMainPostsQuery' );
        
        add_filter( 'tpc_indexer_posts_count_query', $filter_main_posts_query_callback, 10, 2 );

        if ( 'sitemap' == $this->requestedSitemapSlug ) {
            add_filter( 'tpc_indexer_taxonomies_count_query', array( $this, 'filterMainTaxonomiesQuery' ), 10, 2 );   
            add_filter( 'tpc_indexer_media_count_query', $filter_main_posts_query_callback, 10, 2 );
            add_filter( 'tpc_metrics_count_media_inner_query', $filter_main_posts_query_callback, 10, 2 );  
        }
    }

    /**
     * @since 2.0
     * @param string $sitemap_slug
     */
    public function tpcWillServeStylesheet( $sitemap_slug ) {
        $this->addExtraXmlnsNamespacesFilter();

        add_filter( 'tpc_sitemap_stylesheet_extra_columns', array( $this, 'tpcSitemapStylesheetExtraColumns' ) );
    }

    /**
     * @since 2.0
     * @param string $sitemap_slug
     */
    private function addExtraXmlnsNamespacesFilter() {
        add_filter( 'tpc_extra_xmlns_namespaces', array( $this, 'tpcExtraXmlnsNamespaces' ) );
    }

    /**
     * @since 2.0
     * 
     * @param string $extra_xmlns
     * @return string
     */
    public function tpcExtraXmlnsNamespaces( $extra_xmlns ) {
        $extra_xmlns .= ' xmlns:xhtml="http://www.w3.org/1999/xhtml"';

        return $extra_xmlns;
    }

    /**
     * @since 2.0
     * 
     * @param array $extra_columns
     * @return array
     */
    public function tpcSitemapStylesheetExtraColumns( $extra_columns ) {
        $translations_col = array(
            'th_tag'   => '<th class="std-column">' . __( 'Translations', 'the-permalinks-cascade' ) . '</th>',
            'xsl_body' => '<xsl:choose>
                                <xsl:when test="xhtml:link">
                                    <td>
                                        <xsl:for-each select="xhtml:link">
                                            <xsl:if test="position() > 1"> - </xsl:if>
                                            <xsl:variable name="tr_url" select="./@href" />
                                            <a href="{$tr_url}"><xsl:value-of select="./@hreflang" /></a>
                                        </xsl:for-each>
                                    </td>
                                </xsl:when>
                                <xsl:otherwise>
                                    <td>-</td>
                                </xsl:otherwise>
                            </xsl:choose>'
        );

        return ( array( $translations_col ) + $extra_columns );
    }

    /**
     * @since 2.0
     * @param string $sitemap_slug
     */
    public function tpcWillServeSitemap( $sitemap_slug ) {
        global $wpml_url_filters;

        if ( WP_DEBUG ) {
            $this->lineBreak = "\n";
        }

        $wpml_url_filters->remove_global_hooks();
        
        remove_all_filters( 'post_link' );
        remove_all_filters( 'page_link' );
        remove_all_filters( 'post_type_link' );
        remove_all_filters( 'get_term' );
        remove_all_filters( 'term_link' );

        $filter_main_posts_query_callback = array( $this, 'filterMainPostsQuery' );

        switch ( $sitemap_slug ) {
            case 'sitemap':
                $filter_get_permalink_callback = array( $this, 'filterGetPermalink' );

                add_filter( 'post_link', $filter_get_permalink_callback, 10 , 2 );
                add_filter( 'page_link', $filter_get_permalink_callback, 10 , 2 );
                add_filter( 'post_type_link', $filter_get_permalink_callback, 10 , 2 );

                $this->addExtraXmlnsNamespacesFilter();

                add_filter( 'tpc_sitemap_builder_posts_query', $filter_main_posts_query_callback, 10, 2 );
                add_action( 'tpc_sitemap_builder_did_query_posts', array( $this, 'tpcSitemapBuilderDidQueryPosts' ) );
                add_action( 'tpc_sitemap_builder_is_making_url_element', 
                            array( $this, 'tpcSitemapBuilderIsMakingURLElement' ), 10, 2 );
                add_filter( 'tpc_sitemap_builder_taxonomies_query', array( $this, 'filterMainTaxonomiesQuery' ), 10, 2 );
                add_action( 'tpc_sitemap_builder_did_query_taxonomies', array( $this, 'tpcSitemapBuilderDidQueryTaxonomies') );
                add_filter( 'tpc_media_sitemap_builder_posts_query', $filter_main_posts_query_callback, 10, 2 );
                break;

            case 'newsmap':
                add_filter( 'tpc_newsmap_builder_posts_query', $filter_main_posts_query_callback, 10, 2 );
                break;
        }
    }

    /**
     * @since 2.0
     * 
     * @param string $permalink
     * @param object|int $post_or_id
     * @return string
     */
    public function filterGetPermalink( $permalink, $post_or_id ) {
        $post_id = ( is_object( $post_or_id ) ? $post_or_id->ID : $post_or_id );

        if (! isset( $this->trObjects[$post_id] ) ) {
            $tr_data = &$this->trData[$this->currentContentTypeFamily];

            if ( 
                isset( $tr_data[$post_id] ) && 
                ( $tr_data[$post_id]['source_lang'] != $this->siteDefaultLanguage )
            ) {
                return apply_filters( 'wpml_permalink', $permalink, $tr_data[$post_id]['source_lang'] );
            }  
        }
        
        return $permalink;
    }

    /**
     * @since 2.0
     * 
     * @param array $ids
     * @return bool
     */
    public function tpcSitemapBuilderDidQueryPosts( $ids ) {
        global $wpdb;

        $ids_list   = implode( ',', $ids );
        $meta_keys  = $this->db->prepareMetaKey( 'exclude_from_sitemap' );
        $meta_keys .= ',';
        $meta_keys .= $this->db->prepareMetaKey( 'is_ghost_page' );
        
        $tr_data = $wpdb->get_results( "SELECT iclt.element_id, iclt.trid, iclt.language_code, iclt.source_language_code
                                        FROM {$wpdb->prefix}icl_translations AS iclt
                                        LEFT OUTER JOIN {$wpdb->postmeta} AS pm 
                                            ON pm.post_id = iclt.element_id AND pm.meta_key IN ({$meta_keys})
                                        WHERE iclt.trid IN (
                                            SELECT iclt2.trid
                                            FROM {$wpdb->prefix}icl_translations AS iclt2
                                            WHERE iclt2.element_id IN ({$ids_list})
                                        ) AND iclt.element_type = {$this->iclPostType} AND pm.post_id IS NULL
                                        ORDER BY iclt.trid, iclt.language_code" );

        $tr_ids = ( $tr_data ? $this->populateTrDataArray( $tr_data, 'post' ) : array() );

        if (! $tr_ids ) {
            return false;
        }

        $tr_ids_list = implode( ',', $tr_ids );
        $tr_posts    = $wpdb->get_results( "SELECT p.ID, p.post_name, p.post_parent, p.post_type, p.post_status
                                            FROM {$wpdb->posts} AS p
                                            WHERE p.ID IN ({$tr_ids_list}) AND p.post_name NOT IN (
                                                SELECT p2.post_name 
                                                FROM {$wpdb->posts} AS p2
                                                INNER JOIN {$wpdb->prefix}icl_translations AS iclt ON iclt.element_id = p2.ID
                                                WHERE  iclt.element_id IN ({$ids_list}) AND 
                                                       iclt.element_type = {$this->iclPostType}
                                            )" );

        if (! $tr_posts ) {
            return false;
        }

        foreach ( $tr_posts as $post ) {
            $post = sanitize_post( $post, 'raw' );
            
            $this->trObjects[$post->ID] = $post;

            wp_cache_add( $post->ID, $post, 'posts' );
        }
    }

    /**
     * @since 2.0
     * 
     * @param array $unprocessed_tr_data
     * @param string $content_type_id
     * @return array
     */
    public function populateTrDataArray( &$unprocessed_tr_data, $content_type_id ) {
        $this->currentContentTypeFamily = $content_type_id;
        
        $tr_ids       = array();
        $current_trid = $original_element_id = 0;

        foreach ( $unprocessed_tr_data as $data ) {
            if ( (int) $data->trid !== $current_trid ) {
                if ( $original_element_id > 0 ) {
                    $tr_group_data['has_translations'] = (bool) next( $tr_group_data['langs'] );
                    $this->trData[$content_type_id][$original_element_id] = $tr_group_data;
                }

                $original_element_id = 0;
                $current_trid     = (int) $data->trid;
                $tr_group_data    = array( 'source_lang' => '', 'langs' => array() );
            }

            $element_id    = (int) $data->element_id;
            $language_code = sanitize_key( $data->language_code );

            if ( null === $data->source_language_code ) {
                $original_element_id          = $element_id;
                $tr_group_data['source_lang'] = $language_code;
            }
            else {
                $tr_ids[] = $element_id;
            }

            $tr_group_data['langs'][$element_id] = $language_code;
        }

        if ( $original_element_id > 0 ) {
            $tr_group_data['has_translations'] = (bool) next( $tr_group_data['langs'] );
            $this->trData[$content_type_id][$original_element_id] = $tr_group_data;
        }

        return $tr_ids;
    }

    /**
     * @since 2.0
     * 
     * @param object $sitemapBuilder
     * @param string $url
     * @return bool
     */
    public function tpcSitemapBuilderIsMakingURLElement( $sitemapBuilder, $url ) {
        $element_id = $sitemapBuilder->getCurrentID();
        $tr_data    = &$this->trData[$this->currentContentTypeFamily];

        if (! ( isset( $tr_data[$element_id] ) && $tr_data[$element_id]['has_translations'] ) ) {
            return false;
        }

        $tr_links   = '';
        $lang_codes = $tr_data[$element_id]['langs'];
        
        foreach ( $lang_codes as $tr_element_id => $lang_code ) {
            if ( isset( $this->trObjects[$tr_element_id] ) ) {
                if ( 'taxonomy' == $this->currentContentTypeFamily ) {
                    $permalink = get_term_link( $this->trObjects[$tr_element_id] );
                }
                else {
                    $permalink = get_permalink( $this->trObjects[$tr_element_id] );
                }
            }
            else {
                $permalink = $url;
            }

            $tr_url    = apply_filters( 'wpml_permalink', $permalink, $lang_code );
            $tr_links .= '<xhtml:link rel="alternate" hreflang="' . $lang_code . '" href="' . $tr_url . '" />' . $this->lineBreak;    
        }

        $sitemapBuilder->appendToOutput( $tr_links );

        return true;
    }

    /**
     * @since 2.0
     * 
     * @param array $ids
     * @return bool
     */
    public function tpcSitemapBuilderDidQueryTaxonomies( $ids ) {
        global $wpdb;

        $ids_list = implode( $ids, ',' );
        $tr_data  = $wpdb->get_results( "SELECT iclt.element_id, iclt.trid, iclt.language_code, iclt.source_language_code
                                         FROM {$wpdb->prefix}icl_translations AS iclt
                                         WHERE iclt.trid IN (
                                            SELECT iclt2.trid
                                            FROM {$wpdb->prefix}icl_translations AS iclt2
                                            WHERE iclt2.element_id IN ({$ids_list})
                                         ) AND iclt.element_type = {$this->iclTaxonomy}
                                         ORDER BY iclt.trid, iclt.language_code" );

        $tr_ids = ( $tr_data ? $this->populateTrDataArray( $tr_data, 'taxonomy' ) : array() );

        if (! $tr_ids ) {
            return false;
        }

        $tr_ids_list = implode( ',', $tr_ids );
        $tr_terms    = $wpdb->get_results( "SELECT t.term_id, t.slug, tt.term_taxonomy_id, tt.taxonomy
                                            FROM {$wpdb->terms} AS t
                                            INNER JOIN {$wpdb->term_taxonomy} AS tt USING(term_id)
                                            WHERE t.term_id IN ({$tr_ids_list}) AND t.slug NOT IN (
                                                SELECT t2.slug 
                                                FROM {$wpdb->terms} AS t2
                                                INNER JOIN {$wpdb->prefix}icl_translations AS iclt ON iclt.element_id = t2.term_id
                                                WHERE  iclt.element_id IN ({$ids_list}) AND 
                                                       iclt.element_type = {$this->iclTaxonomy}
                                            )" );

        if (! $tr_terms ) {
            return false;
        }

        $this->trObjects = array();

        foreach ( $tr_terms as $term ) {
            $term = sanitize_term( $term, $term->taxonomy, 'raw' );
            
            $this->trObjects[$term->term_id] = $term;

            wp_cache_add( $term->term_id, $term, $term->taxonomy );
        }
    }
}