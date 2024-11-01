<?php
namespace ThePermalinksCascade;

/**
 * @package The Permalinks Cascade
 * @copyright Copyright 2022 Luigi Cavalieri.
 * @license https://opensource.org/licenses/GPL-3.0 GPL v3.0
 *
 * @since 1.0
 */
final class CoreDelegate {
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
    private $indexer;

    /**
     * @since 1.0
     * @var object
     */
    private $paginator;

    /**
     * Slug of the Google Sitemap to serve.
     *
     * @since 1.0
     * @var string
     */
    private $requestedSitemapSlug = '';

    /**
     * ID of the Google Sitemap to serve.
     *
     * @since 1.0
     * @var string
     */
    private $requestedSitemapID;

    /**
     * @since 1.0
     * @var int
     */
    private $requestedSitemapNumber;  

    /**
     * @since 1.0
     * @var int
     */
    private $requestedPageNumber;  

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
     * @param object $wp
     * @return bool Always true.
     */
    public function listenToPageRequest( $wp ) {
        global $wp_query;

        $page_for_site_tree = (int) $this->db->getOption( 'page_for_site_tree', 0 );
        
        if ( ( $page_for_site_tree > 0 ) && $wp_query->is_page() ) {
            $requested_page_id = ( isset( $wp_query->queried_object ) ? $wp_query->queried_object->ID : 0 );

            /**
             * @since 1.0
             */
            $can_filter = apply_filters( 'tpc_can_filter_page_content', 
                                         $wp_query->is_page( $page_for_site_tree ), $page_for_site_tree, $requested_page_id );
            if ( $can_filter ) {
                $this->plugin->load( 'includes/paginator.class.php' );

                $raw_page_number           = $wp_query->get( 'paged' );
                $this->requestedPageNumber = ( $raw_page_number > 1 ) ? $raw_page_number : 1;

                $this->paginator = new Paginator( $this->plugin, $requested_page_id, $this->requestedPageNumber );
                $this->paginator->buildIndexOfPages();

                if ( ( 1 === $raw_page_number ) || !$this->paginator->requestedPageExists() ) {
                    wp_redirect( $this->plugin->sitemapURL( 'site_tree' ), 301 );

                    exit;
                }

                if ( $this->paginator->getNumberOfPages() > 1 ) {
                    remove_action( 'wp_head', 'rel_canonical' );
                    remove_action( 'wp_head', 'wp_shortlink_wp_head' );
                }

                // A priority of 11 registers the method just after the wp_autop() function has run.
                add_filter( 'the_content', array( $this, 'wpWillDisplayPageContent' ), 11 );

                return true;
            } 
        }

        if (! $this->plugin->isSitemapActive( 'sitemap' ) ) {
            return false;
        }

        if ( $wp_query->is_page() && $this->db->getPostMeta( $wp_query->get_queried_object_id(), 'is_ghost_page' ) ) {
            header( 'X-Robots-Tag: noindex, nofollow' );

            // For the WP Super Cache plugin.
            if (! defined( 'DONOTCACHEPAGE' ) ) {
                define( 'DONOTCACHEPAGE', true );
            }
        }
        elseif ( $wp_query->is_robots() ) {
            $this->plugin->load( 'includes/robots-delegate.class.php' );

            $robotsDelegate = new RobotsDelegate( $this->plugin );

            add_filter( 'robots_txt', array( $robotsDelegate, 'wpDidGenerateRobotsFileContent' ), 50, 2 );
        }

        return true;
    }

    /**
     * @since 1.0
     *
     * @param array $headers
     * @param object $wp
     * @return array
     */
    public function wpWillSendHeaders( $headers, $wp ) {
        $this->requestedSitemapSlug = ( isset( $wp->query_vars['tpc'] ) ? $wp->query_vars['tpc'] : '' );
        
        if ( 
            $this->requestedSitemapSlug &&
            ( ( 'sitemap' == $this->requestedSitemapSlug ) || ( 'newsmap' == $this->requestedSitemapSlug ) )
        ) {
            $this->requestedSitemapID = ( isset( $wp->query_vars['id'] ) ? $wp->query_vars['id'] : '' );

            if (! $this->requestedSitemapID ) {
                wp_redirect( $this->plugin->sitemapURL( $this->requestedSitemapSlug ), 301 );

                exit;
            }

            $this->plugin->load( 'includes/indexer.class.php' );

            /**
             * @since 1.0
             */
            do_action( 'tpc_is_processing_sitemap_request', $this->requestedSitemapSlug );

            $this->indexer = new Indexer( $this->plugin, $this->requestedSitemapSlug, $this->requestedSitemapID );

            if ( false === strpos( $this->requestedSitemapID, 'stylesheet' ) ) {
                global $wp_rewrite;

                // If the sitemap is requested via query variable and a permalink
                // structure is in place, it redirects the request to the sitemap's permalink.
                if ( !$wp->did_permalink && $wp_rewrite->using_permalinks() ) {
                    wp_redirect( $this->plugin->sitemapURL( $this->requestedSitemapSlug ), 301 );

                    exit;
                }

                $template_redirect_callback = array( $this, 'serveSingleSitemap' );

                if ( isset( $wp->query_vars['paged'] ) && ( $wp->query_vars['paged'] > 0 ) ) {
                    $this->requestedSitemapNumber = (int) $wp->query_vars['paged'];
                }
                else {
                    $this->requestedSitemapNumber = 0;
                }

                $this->indexer->setRequestedSitemapNumber( $this->requestedSitemapNumber );

                if ( ( 'index' == $this->requestedSitemapID ) && ( 0 === $this->requestedSitemapNumber ) ) {
                    $this->indexer->buildIndex();

                    if ( $this->indexer->getTotalNumberOfSitemaps() > 1 ) {
                        $template_redirect_callback = array( $this, 'serveSitemapIndex' );  
                    }
                }
                elseif ( $this->indexer->isSitemapIDValid() ) {
                    if ( 1 === $this->requestedSitemapNumber ) {
                        wp_redirect( $this->plugin->sitemapURL( $this->requestedSitemapSlug, $this->requestedSitemapID ), 301 );

                        exit;
                    }

                    if (! $this->indexer->requestedSitemapExists() ) {
                        $this->respondWithSitemapNotFound();
                    }
                }
                else {
                    $this->respondWithSitemapNotFound();
                }

                $last_modified = gmdate( 'D, d M Y H:i:s', time() ) . ' GMT';
                $headers       = array(
                    'Content-Type'  => 'application/xml; charset=UTF-8',
                    'Last-Modified' => $last_modified,
                    'Cache-Control' => 'no-cache'
                );
            }
            else {
                $this->plugin->load( 'includes/builders/stylesheet-builder.class.php' );
                
                $headers = array( 'Content-Type' => 'text/xsl; charset=UTF-8' );

                /**
                 * @since 1.0
                 */
                $template_redirect_callback = apply_filters( 'tpc_stylesheet_callback', 
                                                             '', $this->requestedSitemapID, $this->requestedSitemapSlug );

                if (! $template_redirect_callback ) {
                    if ( false === strpos( $this->requestedSitemapID, 'stylesheet' ) ) {
                        $this->respondWithSitemapNotFound();
                    }
                    else {
                        /**
                         * @since 1.0
                         */
                        do_action( "tpc_will_serve_{$this->requestedSitemapID}", $this->requestedSitemapSlug );

                        $stylesheetBuilder = new StylesheetBuilder( $this->plugin, 
                                                                    $this->requestedSitemapSlug,
                                                                    $this->requestedSitemapID );
                   
                        if ( 'index-stylesheet' == $this->requestedSitemapID ) {
                            $template_redirect_callback = array( $stylesheetBuilder, 'serveIndexStylesheet' );
                        }
                        else {
                            $template_redirect_callback = array( $stylesheetBuilder, 'serveStylesheet' );

                            $this->indexer->buildIndex();
                            $stylesheetBuilder->setSitemapIsPartOfCollection( $this->indexer->getTotalNumberOfSitemaps() > 1 );
                        }
                    }
                }
            }

            add_action( 'template_redirect', $template_redirect_callback );
            remove_filter( 'template_redirect', 'redirect_canonical' );
        }
        
        return $headers;
    }

    /**
     * @since 1.0
     */
    private function respondWithSitemapNotFound() {
        header( 'HTTP/1.0 404 Not Found' );
                            
        exit;
    }

    /**
     * @since 1.0
     */
    public function serveSitemapIndex() {
        // For the WP Super Cache plugin.
        define( 'DONOTCACHEPAGE', true );

        $linebreak      = ( WP_DEBUG ? "\n" : '' );
        $plugin_version = $this->plugin->version();
        $index          = $this->indexer->getIndexOfSitemaps();

        $markup = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
                . '<?xml-stylesheet type="text/xsl" href="' . home_url( "/{$this->requestedSitemapSlug}-index-template.xsl" ) 
                . '?ver=' . $plugin_version . '"?>' . "\n"
                . '<!-- Sitemap Index generated by ' . $this->plugin->name() . ' ' . $plugin_version 
                . ' (' . $this->plugin->pluginURI() . ") -->\n"
                . '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ( $index as $sitemap_id => $number_of_sitemaps ) {
            for ( $i = 1; $i <= $number_of_sitemaps; $i++ ) {
                $markup .= '<sitemap>' . $linebreak
                         . '<loc>' . $this->plugin->sitemapURL( $this->requestedSitemapSlug, $sitemap_id, $i ) . '</loc>' . $linebreak
                         . '</sitemap>' . $linebreak;
            }
        }

        $markup .= '</sitemapindex>';

        $this->updateMetrics();

        exit( $markup );
    }

    /**
     * @since 1.0
     * @return bool
     */
    public function serveSingleSitemap() {
        // For the WP Super Cache plugin.
        define( 'DONOTCACHEPAGE', true );

        $this->plugin->load( 'includes/builders/builder-core.class.php' );
        $this->plugin->load( 'includes/builders/google-sitemap-builder.class.php' );

        $stylesheet_id = $this->requestedSitemapSlug;

        /**
         * @since 1.0
         */
        do_action( 'tpc_will_serve_sitemap', $this->requestedSitemapSlug, $this->requestedSitemapID );

        switch ( $this->requestedSitemapSlug ) {
            case 'sitemap':
                switch ( $this->requestedSitemapID ) {
                    case 'image':
                        $this->plugin->load( 'includes/builders/media-sitemap-builder.class.php' );
                        $this->plugin->load( 'includes/builders/media-element.class.php' );
                        $this->plugin->load( 'includes/builders/image-sitemap-builder.class.php' );

                        $stylesheet_id .= '-image';
                        $extra_xmlns    = 'xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"';
                        $builder        = new ImageSitemapBuilder( $this->plugin, $this->indexer );
                        break;

                    case 'video':
                        $this->plugin->load( 'includes/builders/media-sitemap-builder.class.php' );
                        $this->plugin->load( 'includes/builders/media-element.class.php' );
                        $this->plugin->load( 'includes/builders/video-sitemap-builder.class.php' );

                        $stylesheet_id .= '-video';
                        $extra_xmlns    = 'xmlns:video="http://www.google.com/schemas/sitemap-video/1.1"';
                        $builder        = new VideoSitemapBuilder( $this->plugin, $this->indexer );
                        break;

                    default:
                        $this->plugin->load( 'includes/builders/builders-interfaces.php' );
                        $this->plugin->load( 'includes/builders/sitemap-builder.class.php' );

                        $builder = new SitemapBuilder( $this->plugin, $this->indexer );
                        
                        /**
                         * @since 1.0
                         */
                        $extra_xmlns = apply_filters( 'tpc_extra_xmlns_namespaces', '' );
                        break;
                }
                break;

            case 'newsmap':
                $this->plugin->load( 'includes/builders/newsmap-builder.class.php' );

                $builder     = new NewsmapBuilder( $this->plugin, $this->indexer );
                $extra_xmlns = 'xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"';
                break;

            default:
                return false;
        }

        $sitemap = $builder->build();

        $this->updateMetrics( $builder );

        $plugin_version  = $this->plugin->version();
        $stylesheet_name = $stylesheet_id . '-template.xsl';

        exit( '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<?xml-stylesheet type="text/xsl" href="' . home_url( $stylesheet_name ) 
            . '?ver=' . $plugin_version . '"?>' . "\n"
            . '<!-- Sitemap generated by ' . $this->plugin->name() . ' ' . $plugin_version
            . ' (' . $this->plugin->pluginURI() . ") -->\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" ' . $extra_xmlns 
            . '>' . $sitemap . '</urlset>' );
    }
    
    /**
     * Appends the Site Tree to the content of the page where the Site Tree must be shown.
     *
     * This method is hooked into the the_content filter hook.
     *
     * @since 1.0
     *
     * @param string $the_content
     * @return string
     */
    public function &wpWillDisplayPageContent( $the_content ) {
        if ( in_the_loop() ) {
            $builder = $this->plugin->invokeGlobalObject( 'SiteTreeBuilder' );
            $builder->setPaginator( $this->paginator );
            
            $the_content .= "<!-- Site Tree start -->\n";
            $the_content .= $builder->build();
            $the_content .= "<!-- Site Tree end -->\n";

            $this->updateMetrics( $builder );

            remove_filter( 'the_content', array( $this, 'wpWillDisplayPageContent' ), 11 );
        }

        return $the_content;
    }

    /**
     * @since 1.0
     * 
     * @param objetc $builder
     * @return bool
     */
    private function updateMetrics( $builder = null ) {
        if ( $this->requestedSitemapSlug ) {
            $sitemap_slug = $this->requestedSitemapSlug;
        }
        else {
            $sitemap_slug = ( is_object( $builder ) ? $builder->sitemapSlug() : '' );
        }
        
        $metrics = (array) $this->db->getNonAutoloadOption( 'metrics', array(), $sitemap_slug );

        switch ( $sitemap_slug ) {
            case 'sitemap':
            case 'newsmap':
                $sitemap_uid      = $this->indexer->getRequestedSitemapUID();
                $sitemap_is_index = ( $builder === null );

                if ( $this->db->getNonAutoloadOption( 'metrics', false, 'metrics_are_fresh', $sitemap_slug ) ) {
                    if ( $sitemap_is_index || isset( $metrics['metrics_per_document']['num_queries'][$sitemap_uid] ) ) {
                        return false;
                    }
                }

                if ( $this->indexer->hasIndexJustBeenBuilt() ) {
                    $metrics['tot_sitemaps'] = $this->indexer->getTotalNumberOfSitemaps();
                    $metrics['tot_items']    = $this->indexer->getTotalNumberOfPermalinks();

                    if ( 'sitemap' == $sitemap_slug ) {
                        $metrics['tot_images'] = $this->countTotalNumberOfMedia( 'image' );
                        $metrics['tot_videos'] = $this->countTotalNumberOfMedia( 'video' );
                    }
                }

                if (! $sitemap_is_index ) {
                    $new_metrics = $builder->getMetrics();

                    $metrics['num_queries'] = $new_metrics['num_queries'];
                    $metrics['runtime']     = $new_metrics['runtime'];
                    $metrics['metrics_per_document']['runtime'][$sitemap_uid]     = $new_metrics['runtime'];
                    $metrics['metrics_per_document']['num_queries'][$sitemap_uid] = $new_metrics['num_queries'];
                    
                    if ( isset( $metrics['tot_sitemaps'] ) && ( $metrics['tot_sitemaps'] > 1 ) ) {
                        $this->computeMetricsAverageValues( $metrics );
                    }
                    else {
                        unset( $metrics['avg_num_queries'], $metrics['avg_runtime'] );
                    }
                }
                break;

            case 'site_tree':
                if ( $this->db->getNonAutoloadOption( 'metrics', false, 'metrics_are_fresh', $sitemap_slug ) ) {
                    if ( isset( $metrics['metrics_per_document']['num_queries'][$this->requestedPageNumber] ) ) {
                        return false;
                    }
                }

                $new_metrics = $builder->getMetrics();
                $tot_items = $this->paginator->getTotalNumberOfItems();
                
                // If $tot_items > 0 it means that the index has just been built.
                if ( $tot_items > 0 ) {
                    $metrics['tot_pages'] = $this->paginator->getNumberOfPages();
                    $metrics['tot_items'] = $tot_items;
                }

                $metrics['num_queries'] = $new_metrics['num_queries'];
                $metrics['runtime']     = $new_metrics['runtime'];
                $metrics['metrics_per_document']['runtime'][$this->requestedPageNumber]     = $new_metrics['runtime'];
                $metrics['metrics_per_document']['num_queries'][$this->requestedPageNumber] = $new_metrics['num_queries'];
                
                if ( $metrics['tot_pages'] > 1 ) {
                    $this->computeMetricsAverageValues( $metrics );
                }
                else {
                    unset( $metrics['avg_num_queries'], $metrics['avg_runtime'] );
                }
                break;

            default:
                return false;
        }

        $metrics['metrics_computed_on'] = time();
        $metrics['metrics_are_fresh']   = true;

        $this->db->setNonAutoloadOption( 'metrics', $metrics, $sitemap_slug );

        return true;
    }

    /**
     * @since 2.0
     *
     * @param string $media_type
     * @return int
     */
    private function countTotalNumberOfMedia( $media_type ) {
        global $wpdb;

        $post_types_list = $this->indexer->getPostTypesList();

        if (! $post_types_list ) {
            return -1;
        }

        $meta_keys  = $this->db->prepareMetaKey( 'exclude_from_sitemap' );
        $meta_keys .= ',';
        $meta_keys .= $this->db->prepareMetaKey( 'is_ghost_page' );

        $inner_query = array(
            'SELECT'          => 'p.ID',
            'FROM'            => "{$wpdb->posts} AS p",
            'LEFT_OUTER_JOIN' => "{$wpdb->postmeta} AS pm ON p.ID = pm.post_id AND pm.meta_key IN ({$meta_keys})",
            'WHERE'           => "p.post_type IN({$post_types_list}) AND 
                                  p.post_status = 'publish' AND p.post_password = '' AND pm.post_id IS NULL"
        );

        /**
         * @since 1.0
         */
        $inner_query = apply_filters( 'tpc_metrics_count_media_inner_query', $inner_query, $post_types_list );
        $inner_query = $this->db->mergeQueryClauses( $inner_query );

        $results = $wpdb->get_results(
            "SELECT COUNT( p1.ID ) AS count
             FROM {$wpdb->posts} AS p1
             WHERE p1.post_parent IN ({$inner_query}) AND 
                   p1.post_type = 'attachment' AND p1.post_mime_type LIKE '{$media_type}/%' AND
                   p1.post_modified >= COALESCE( 
                       ( SELECT p_temp.post_modified
                         FROM {$wpdb->posts} AS p_temp
                         WHERE p_temp.post_type = 'attachment' AND
                               p_temp.post_mime_type LIKE '{$media_type}/%' AND
                               p_temp.post_parent = p1.post_parent
                         ORDER BY p_temp.post_modified DESC
                         LIMIT 999, 1
                        ),
                        p1.post_modified
                   )"
        );

        return $results[0]->count;
    }

    /**
     * @since 1.0
     * @param array $metrics
     */
    private function computeMetricsAverageValues( &$metrics ) {
        $metrics_per_document = $metrics['metrics_per_document'];

        foreach( $metrics_per_document as $key => $metric_values ) {
            $sum     = $num_values = 0;
            $avg_key = 'avg_' . $key;

            foreach ( $metric_values as $metric_value ) {
                $num_values += 1;
                $sum        += $metric_value;
            }

            if ( $sum == (int) $sum ) {
                $metrics[$avg_key] = ceil( $sum / $num_values );
            }
            else {
                $metrics[$avg_key] = round( ( $sum / $num_values ), 3 );
            }

            unset( $metrics[$key] );
        }
    }
}