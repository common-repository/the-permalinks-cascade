<?php
namespace ThePermalinksCascade;

/**
 * @package The Permalinks Cascade
 * @copyright Copyright 2022 Luigi Cavalieri.
 * @license https://opensource.org/licenses/GPL-3.0 GPL v3.0
 *
 * @since 1.0
 */
final class Core extends BasePlugin {
    /**
     * @see parent::finishLaunching()
     * @since 1.0
     */
    public function finishLaunching() {
        if (! $this->verifyWordPressCompatibility() ) {
            return false;
        }

        $this->initDB( 'tpc' );

        if ( $this->isUninstalling ) {
            return true;
        }

        $this->load( 'library/functions.php' );

        $is_admin = is_admin();

        if ( $is_admin && wp_doing_ajax() ) {
            $adminController = $this->invokeGlobalObject( 'AdminController' );

            add_action( 'init', array( $adminController, 'registerTopicTaxonomy' ) );
            add_action( 'save_post', array( $adminController, 'wpDidSavePostViaAjax' ), 10, 2 );
            add_action( 'wp_ajax_handleTPCAdminAjaxRequest', array( $adminController, 'handleTPCAdminAjaxRequest' ) );

            return true;
        }
        
        if ( !$is_admin && $this->isSitemapActive( 'sitemap' ) ) {
            add_filter( 'wp_sitemaps_enabled', '__return_false' ); 
        }
        
        add_action( 'init', array( $this, 'pluginDidFinishLaunching' ) );
        
        return true;
    }

    /**
     * @see parent::pluginDidFinishLaunching()
     * @since 1.0
     */
    public function pluginDidFinishLaunching() {
        $this->verifyVersionOfStoredData();

        $is_sitemap_active                = $this->isSitemapActive( 'sitemap' );
        $there_are_google_sitemaps_active = ( $is_sitemap_active || $this->isSitemapActive( 'newsmap' ) );

        if ( $there_are_google_sitemaps_active ) {
            global $wp;

            $wp->add_query_var( 'tpc' );
            $wp->add_query_var( 'id' );

            $this->registerRewriteRules();
        }

        $hyperlistController = $this->invokeGlobalObject( 'HyperlistController' );

        add_action( 'wp_loaded', array( $hyperlistController, 'wpDidFinishLoading' ) );

        if ( is_admin() ) {
            $this->maybeLoadModules();

            add_action( 'wp_loaded', array( $this->invokeGlobalObject( 'AdminController' ), 'wpDidFinishLoading' ) );
        }
        elseif ( 0 === strpos( $_SERVER['REQUEST_URI'], '/wp-json/' ) ) {
            $adminController = $this->invokeGlobalObject( 'AdminController' );

            add_action( 'save_post', array( $adminController, 'wpDidSavePostViaRestRequest' ), 10, 2 );
            add_action( 'trashed_post', array( $adminController, 'wpDidTrashPost' ) );
        }
        else {
            $this->maybeLoadModules();
            $this->load( 'includes/core-delegate.class.php' );
            
            $coreDelegate = new CoreDelegate( $this );
            
            add_action( 'wp', array( $coreDelegate, 'listenToPageRequest' ), 5 );
            add_shortcode( 'permalinks-cascade', array( $hyperlistController, 'doShortcode' ) );

            if ( $there_are_google_sitemaps_active ) {
                add_filter( 'wp_headers', array( $coreDelegate, 'wpWillSendHeaders' ), 10, 2 );  
            }
        }
        
        return true;
    }
    
    /**
     * Verifies that the data stored into the database are compatible with 
     * this version of the plugin and if needed invokes the upgrader.
     *
     * @since 1.0
     * @return bool
     */
    private function verifyVersionOfStoredData() {
        $current_version = $this->db->getOption( 'version' );
        
        if ( $current_version === $this->version ) {
            return true;
        }

        $is_tpc_first_launch = ( false === $current_version );

        if ( $is_tpc_first_launch ) {
            if ( is_array( get_option( 'the_permalinks_cascade_pro' ) ) ) {
                $this->db->overwriteOptions( get_option( 'the_permalinks_cascade_pro' ) );
                
                delete_option( 'the_permalinks_cascade_pro' );
            }
            elseif ( is_array( get_option( 'sitetree' ) ) ) {
                $this->load( 'library/plugin-upgrader.class.php' );
                $this->load( 'includes/sitetree-migrator.class.php' );

                $migrator = new SiteTreeMigrator( $this );
                $migrator->migrateData();
            }
        }
        elseif ( $current_version ) {
            // To be enabled in a future release.
            //
            // $this->load( 'library/plugin-upgrader.class.php' );
            // $this->load( 'includes/upgrader.class.php' );

            // $upgrader = new Upgrader( $this );
            // $upgrader->upgrade( $current_version );
        }

        $now = time();

        if ( $is_tpc_first_launch || !$this->db->getOption( 'installed_on' ) ) {
            $this->db->setOption( 'installed_on', $now );
        }

        $this->db->setOption( 'last_updated', $now );
        $this->db->setOption( 'version', $this->version );
        
        return true;
    }

    /**
     * @since 2.0
     * @return bool
     */
    private function maybeLoadModules() {
        if ( !( class_exists( 'SitePress' ) &&  defined( 'ICL_SITEPRESS_VERSION' ) ) ) {
            return false;
        }

        if ( version_compare( ICL_SITEPRESS_VERSION, '4.4', '<' ) ) {
            $message = 'The WPML Module of ' . $this->name() . ' cannot load because it requires WPML 4.4 or later.';

            $this->registerAdminNoticeActionWithMessage( $message );

            return false;
        }

        $this->load( 'includes/modules/wpml-module.class.php' );

        WPMLModule::launch( $this );

        return true;
    }

    /**
     * @since 1.0
     * @return bool|int
     */
    public function registerRewriteRules() {
        add_action( 'generate_rewrite_rules', array( $this, 'wpRewriteDidGenerateRules' ) );
    }

    /**
     * @since 1.0
     * @param object $wp_rewrite
     */
    public function wpRewriteDidGenerateRules( $wp_rewrite ) {
        $rules = array(
            '^(sitemap|newsmap)-([a-z]+-)?template\.xsl$' => 'index.php?tpc=$matches[1]&id=$matches[2]stylesheet'
        );

        if ( $this->isSitemapActive( 'newsmap' ) ) {
            $rules['^news-sitemap\.xml$']                         = 'index.php?tpc=newsmap&id=index';
            $rules['^([_a-z]+)-news-sitemap(?:-([0-9]+))?\.xml$'] = 'index.php?tpc=newsmap&id=$matches[1]&paged=$matches[2]';
        }

        if ( $this->isSitemapActive( 'sitemap' ) ) {
            $sitemap_filename = $this->getSitemapFilename();
            
            $rules["^{$sitemap_filename}\.xml$"]                         = 'index.php?tpc=sitemap&id=index';
            $rules["^([_a-z]+)-{$sitemap_filename}(?:-([0-9]+))?\.xml$"] = 'index.php?tpc=sitemap&id=$matches[1]&paged=$matches[2]';
        }

        /**
         * @since 1.0
         */
        $rules = apply_filters( 'tpc_did_generate_rewrite_rules', $rules, $this );

        $wp_rewrite->rules = $rules + $wp_rewrite->rules;
    }

    /**
     * @since 1.0
     * @return string
     */
    public function getSitemapFilename() {
        $filename = sanitize_key( $this->db->getOption( 'sitemap_filename', 'sitemap' ) );

        return ( $filename ? $filename : 'sitemap' );
    }

    /**
     * @since 1.0
     *
     * @param string $sitemap_id
     * @return bool
     */
    public function isSitemapActive( $sitemap_id ) {
        if ( 'site_tree' == $sitemap_id ) {
            return (bool) $this->db->getOption( 'page_for_site_tree' );    
        }

        return (bool) $this->db->getOption( $sitemap_id, false, 'is_sitemap_active' );
    }

    /**
     * @since 1.0
     *
     * @param string $sitemap_slug
     * @param string $sitemap_id
     * @param int $sitemap_number
     * @return string
     */
    public function sitemapURL( $sitemap_slug, $sitemap_id = '', $sitemap_number = 0 ) {
        global $wp_rewrite;

        switch ( $sitemap_slug ) {
            case 'sitemap':
            case 'newsmap':
                if ( $wp_rewrite->using_permalinks() ) {
                    if (! $sitemap_id ) {
                        if ( 'sitemap' == $sitemap_slug ) {
                            $relative_url = '/' . $this->getSitemapFilename() . '.xml';

                            return home_url( $relative_url );
                        }
                        
                        return home_url( '/news-sitemap.xml' );
                    }

                    $relative_url = '/' . $sitemap_id;

                    if ( 'sitemap' == $sitemap_slug ) {
                        $relative_url .= '-' . $this->getSitemapFilename();
                    }
                    else {
                        $relative_url .= '-news-sitemap';
                    }

                    if ( $sitemap_number > 1 ) {
                        $relative_url .= '-' . $sitemap_number;
                    }

                    $relative_url .= '.xml';

                    return home_url( $relative_url );
                }

                $arguments = array( 'tpc' => $sitemap_slug );

                if ( $sitemap_id ) {
                    $arguments['id'] = $sitemap_id;
                }

                if ( $sitemap_number > 1 ) {
                    $arguments['paged'] = $sitemap_number;
                }
                
                return add_query_arg( $arguments, home_url( '/' ) );

            case 'site_tree':
                $permalink = get_permalink( $this->db->getOption( 'page_for_site_tree' ) );
                
                if ( $sitemap_number > 1 ) {
                    if ( $wp_rewrite->using_permalinks() ) {
                        $permalink .= 'page/' . $sitemap_number . '/';
                    }
                    else {
                        return add_query_arg( 'paged', $sitemap_number, $permalink );
                    }
                }

                return $permalink;
        }

        return '';
    }

    /**
     * @since 1.0
     *
     * @param string $content_type
     * @param string $sitemap_slug
     * @param bool $default
     * @return bool
     */
    public function isContentTypeIncluded( $content_type, $sitemap_slug, $default = false ) {
        $option_key_group = $sitemap_slug . '_content_types';

        return (bool) $this->db->getOption( $content_type, $default, $option_key_group );
    }

    /**
     * @since 1.0
     * @return bool
     */
    public function isWebsiteLocal() {
        if ( WP_DEBUG ) {
            return false;
        }

        $site_url = site_url();

        if ( false === strpos( $site_url, '.' ) ) {
            return true;
        }
        
        $known_local_patterns = array(
            '#\.local$#i',
            '#\.localhost$#i',
            '#\.test$#i',
            '#\.staging$#i',     
            '#\.stage$#i',
            '#^dev\.#i',
            '#^stage\.#i',
            '#^staging\.#i',
        );

        $host = parse_url( $site_url, PHP_URL_HOST );

        foreach( $known_local_patterns as $pattern ) {
            if ( preg_match( $pattern, $host ) ) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * @since 1.0
     * @param string $sitemap_slug
     */
    public function flushCachedData( $sitemap_slug ) {
        switch ( $sitemap_slug ) {
            case 'site_tree':
                if ( defined( 'WP_CACHE' ) && WP_CACHE && function_exists( 'wpsc_delete_url_cache' ) ) {
                    $index_of_pages = (array) $this->db->getNonAutoloadOption( 'site_tree_index', 
                                                                               array(), 
                                                                               (int) $this->db->getOption( 'page_for_site_tree', 0 ) );
                    
                    reset( $index_of_pages );

                    do {
                        $page_number = key( $index_of_pages );

                        wpsc_delete_url_cache( $this->sitemapURL( 'site_tree', '', $page_number ) );
                    } while( next( $index_of_pages ) );
                }
                break;

            case 'advanced':
                $sitemap_slug = 'sitemap';
                break;
        }
        
        $this->db->deleteNonAutoloadOption( "{$sitemap_slug}_index" );
        $this->db->setNonAutoloadOption( 'metrics', false, 'metrics_are_fresh', $sitemap_slug );

        /**
         * @since 1.0
         */
        do_action( 'tpc_did_flush_cached_data', $sitemap_slug );
    }
}