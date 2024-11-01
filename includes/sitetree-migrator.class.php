<?php
namespace ThePermalinksCascade;

/**
 * @package The Permalinks Cascade
 * @copyright Copyright 2022 Luigi Cavalieri.
 * @license https://opensource.org/licenses/GPL-3.0 GPL v3.0
 *
 * @since 1.0
 */
final class SiteTreeMigrator extends PluginUpgrader {
    /**
     * @since 1.0
     * @var object
     */
    private $sitetreeDB;

    /**
     * @see parent::__construct()
     * @since 1.0
     */
    public function __construct( $plugin ) {
        parent::__construct( $plugin );

        $this->sitetreeDB = new DB( 'sitetree' );
    }

    /**
     * @see parent::upgrade()
     * @since 1.0
     */
    public function migrateData() {
        $version_to_migrate_from = $this->sitetreeDB->getOption( 'version' );

        if ( version_compare( $version_to_migrate_from, '1.5.3', '<=' ) ) {
            $this->upgradeExclusions( 'xml', 'sitemap' );
            $this->upgradeExclusions( 'html5', 'site_tree' );
            $this->deletePriorityAndChangefreqMetadata();

            $this->sitetreeDB->overwriteOptions( array() );
            
            delete_option( '_sitetree_backup' );
            delete_transient( 'sitetree_html5' );
            delete_transient( 'sitetree_xml' );
        }
        elseif ( version_compare( $version_to_migrate_from, '6.0', '>=' ) ) {
            if ( version_compare( $version_to_migrate_from, '6.0.2', '<' ) ) {
                $this->includePageContentTypeInSitemap();
            }
        }
        elseif ( version_compare( $version_to_migrate_from, '5.0', '>=' ) ) {
            if ( version_compare( $version_to_migrate_from, '5.1', '<' ) ) {
                $this->renameExcludeChildsOption();
            }
            elseif ( $version_to_migrate_from == '5.3.2' ) {
                $this->sitetreeDB->deleteOption( 'ask4donation_clicked' );
            }

            $this->includePageContentTypeInSitemap();
            $this->sitetreeDB->deleteNonAutoloadOption( 'stats' );
        }
        elseif ( version_compare( $version_to_migrate_from, '4.0', '>=' ) ) {
            if ( version_compare( $version_to_migrate_from, '4.3', '<' ) ) {
                $this->upgradePositionOptions();
            }

            if ( version_compare( $version_to_migrate_from, '4.5.2', '<' ) ) {
                $this->convertLimitOptions();
            }

            $this->upgradeSitemapExcludedTaxonomyIDsOptions();
            $this->renameExcludeChildsOption();
            $this->deletePriorityAndChangefreqMetadata();
            
            $this->sitetreeDB->deleteNonAutoloadOption( 'stats' );
        }
        else {
            if ( version_compare( $version_to_migrate_from, '3.0', '>=' ) ){
                $this->sitetreeDB->deleteOption( 0, 'site_tree' );
                $this->renameExcludeChildsOption();
            }
            else {
                if ( version_compare( $version_to_migrate_from, '2.0.2', '<' ) ) {
                    $this->sitetreeDB->deleteOption( 'is_first_activation' );
                }
                
                if ( version_compare( $version_to_migrate_from, '2.1', '<' ) ) {
                    $this->sitetreeDB->deleteOption( 'cache' );
                    $this->sitetreeDB->deleteOption( 'show_credit' );
                    $this->sitetreeDB->deleteOption( 'ask4help_displayed' );
                }

                if ( version_compare( $version_to_migrate_from, '2.2', '<' ) ) {
                    $this->upgradeExcludedAuthorsList();
                }

                $this->upgradeIncludeOptions();
                $this->sitetreeDB->deleteOption( 'home_changefreq' );
                $this->sitetreeDB->deleteOption( 'page', 'sitemap' );
                $this->sitetreeDB->deleteOption( 'post', 'sitemap' );
                $this->sitetreeDB->deleteOption( 'authors', 'sitemap' );
                $this->sitetreeDB->deleteOption( 'changefreq', 'category', 'sitemap' );
                $this->sitetreeDB->deleteOption( 'changefreq', 'post_tag', 'sitemap' );
                $this->sitetreeDB->deleteOption( 'priority', 'category', 'sitemap' );
                $this->sitetreeDB->deleteOption( 'priority', 'post_tag', 'sitemap' );
            }

            $this->upgradePositionOptions();
            $this->sitetreeDB->deleteOption( 'sitemap_info' );
            $this->sitetreeDB->deleteOption( 'site_tree_info' );
            $this->sitetreeDB->deleteOption( 'items_limit' );

            $options = $this->sitetreeDB->getOptions();
            $options = self::moveArrayElement( $options, array( 'sitemap_active' ), array( 'is_sitemap_active', 'sitemap' ) );
            $options = self::renameArrayKeys( $options, array( 'site_tree', 'tags' ), array( 'site_tree', 'post_tag' ) );
            $options = self::renameArrayKeys( $options, 
                                              array( 'site_tree_content_types', 'tags' ),
                                              array( 'site_tree_content_types', 'post_tag' ) );

            $this->sitetreeDB->overwriteOptions( $options );
            $this->deletePriorityAndChangefreqMetadata();

            delete_transient( 'sitetree_site_tree' );
            delete_transient( 'sitetree_sitemap' );
        }

        $this->db->overwriteOptions( $this->sitetreeDB->getOptions() );
        $this->migrateMetrics();
        $this->migratePingData();
        $this->renameShortcodes();

        $this->wpdb->query(
            "UPDATE {$this->wpdb->postmeta} 
             SET meta_key = REPLACE( meta_key, '_sitetree_', '{$this->db->metaKeyPrefix()}' )"
        );

        $this->wpdb->query(
            "UPDATE {$this->wpdb->postmeta} 
             SET meta_key = {$this->db->prepareMetaKey( 'exclude_from_hyperlists' )}
             WHERE meta_key = {$this->db->prepareMetaKey( 'exclude_from_shortcode_lists' )}"
        );

        $this->wpdb->query(
            "UPDATE {$this->wpdb->term_taxonomy}
             SET taxonomy = '{$this->db->prefixDBKey( 'topic' )}' WHERE taxonomy = 'sitetree_topic'"
        );

        $this->plugin->registerRewriteRules();
        flush_rewrite_rules( false );
    }

    /**
     * @since 1.0
     *
     * @param string $old_context
     * @param string $new_context
     * @return bool
     */
    private function upgradeExclusions( $old_context, $new_context ) {
        $ids = $this->sitetreeDB->getOption( $old_context, array(), 'exclude' );

        if (! ( $ids && is_array( $ids ) ) ) {
            return false;
        }

        $list_of_ids = '';

        foreach ( $ids as $id ) {
            $id = (int) $id;

            if ( $id > 0 ) {
                $list_of_ids .= $id . ',';
            }
        }

        // Removes the trailing comma from the string.
        $list_of_ids = substr( $list_of_ids, 0, -1);

        if (! $list_of_ids ) {
            return false;
        }

        $meta_key = $this->db->prepareMetaKey( 'exclude_from_' . $new_context );

        $this->wpdb->query(
            "INSERT INTO {$this->wpdb->postmeta} (post_id, meta_key, meta_value)
                SELECT ID, '{$meta_key}', 1 FROM {$this->wpdb->posts} AS p
                LEFT OUTER JOIN {$this->wpdb->postmeta} AS pm
                             ON p.ID = pm.post_id AND pm.meta_key = '{$meta_key}'
                WHERE ID IN ({$list_of_ids}) AND post_type IN ('post', 'page') AND 
                            post_status = 'publish' AND pm.post_id IS NULL"
        );

        return true;
    }

    /**
     * Converts a comma-separated list of display names into a
     * comma-separated list of nicknames.
     *
     * @since 1.0
     * @return bool
     */
    private function upgradeExcludedAuthorsList() {
        $excluded_authors_list = $this->sitetreeDB->getOption( 'exclude', '', 'authors', 'site_tree' );

        if (! $excluded_authors_list ) {
            return false;
        }

        $excluded_authors = explode( ',', $excluded_authors_list );

        if (! $excluded_authors ) {
            return false;
        }

        $nicknames = $display_names = array();
        $users     = get_users();

        foreach ( $excluded_authors as $display_name ) {
            $display_name = trim( $display_name );

            if ( preg_match( '/[^a-zA-Z\040\.-]/', $display_name ) === 0 ) {
                $display_names[$display_name] = $display_name;
            }
        }

        foreach ( $users as $user ) {
            if ( isset( $display_names[$user->display_name] ) ) {
                $nicknames[] = sanitize_text_field( $user->user_nicename );
            }
        }

        if (! $nicknames ) {
            return false;
        }

        $this->sitetreeDB->setOption( 'exclude', implode( ', ', $nicknames ), 'authors', 'site_tree' );

        return true;
    }

    /**
     * @since 1.0
     */
    private function upgradeIncludeOptions() {
        $content_flags = array(
            'page'     => false,
            'post'     => false,
            'authors'  => false,
            'category' => false,
            'post_tag' => false
        );

        foreach ( array( 'sitemap', 'site_tree' ) as $sitemap_id )  {
            if ( is_array( $this->sitetreeDB->getOption( $sitemap_id ) ) ) {
                $sitemap_content_flags   = $content_flags;
                $content_types_option_id = $sitemap_id . '_content_types';
                $at_least_one_content_type_is_included = false;

                foreach ( $sitemap_content_flags as $content_type => $flag ) {
                    if ( $this->sitetreeDB->getOption( 'include', false, $content_type, $sitemap_id ) ) {
                        $sitemap_content_flags[$content_type]  = true;
                        $at_least_one_content_type_is_included = true;
                    }

                    $this->sitetreeDB->deleteOption( 'include', $content_type, $sitemap_id );
                }

                if (! $at_least_one_content_type_is_included ) {
                    $sitemap_content_flags['page'] = true;
                }

                $this->sitetreeDB->setOption( $content_types_option_id, $sitemap_content_flags );
            }
            
            $content_flags['tags'] = false;

            unset( $content_flags['post_tag'] );
        }
    }

    /**
     * @since 1.0
     * @return bool
     */
    private function upgradePositionOptions() {
        $site_tree_content_options = $this->sitetreeDB->getOption( 'site_tree' );
        
        if (! is_array( $site_tree_content_options ) ) {
            return false;
        }

        $ordered_content_types = array();
        $keys          = array_keys( $site_tree_content_options );

        foreach ( $keys as $key ) {
            $ordered_content_types[$key] = false;

            unset( $site_tree_content_options[$key]['position'] );
        }

        $site_tree_content_types = $this->sitetreeDB->getOption( 'site_tree_content_types' );

        if (! is_array( $site_tree_content_types ) ) {
            return false;
        }

        foreach ( $site_tree_content_types as $key => $value ) {
            $ordered_content_types[$key] = (bool) $value;
        }

        $this->sitetreeDB->setOption( 'site_tree', $site_tree_content_options );
        $this->sitetreeDB->setOption( 'site_tree_content_types', $ordered_content_types );

        return true;
    }

    /**
     * @since 1.0
     */
    private function convertLimitOptions() {
        $post_types = get_post_types( array( 'public' => true ) );
        
        foreach ( $post_types as $post_type ) {
            $limit = $this->sitetreeDB->getOption( 'limit', false, $post_type, 'site_tree' );

            if ( $limit && ( $limit < 0 )  ) {
                $this->sitetreeDB->setOption( 'limit', 1000, $post_type, 'site_tree' );
            }
        } 
    }

    /**
     * @since 1.0
     */
    private function upgradeSitemapExcludedTaxonomyIDsOptions() {
        $taxonomies = get_taxonomies( array( 'public' => true ) );

        foreach( $taxonomies as $taxonomy_name ) {
            $list_of_ids = (string) $this->sitetreeDB->getOption( 'exclude', '', $taxonomy_name, 'sitemap' );

            if ( $list_of_ids ) {
                $this->sitetreeDB->setOption( $taxonomy_name, $list_of_ids, 'exclude_from_sitemap' );
                $this->sitetreeDB->deleteOption( 'exclude', $taxonomy_name, 'sitemap' );
            }
        }
    }

    /**
     * @since 1.0
     */
    private function deletePriorityAndChangefreqMetadata() {
        $this->wpdb->query( 
            "DELETE FROM {$this->wpdb->postmeta} 
             WHERE meta_key IN ( '_sitetree_priority', '_sitetree_changefreq' )"
        );
    }

    /**
     * @since 1.0
     */
    private function renameExcludeChildsOption() {
        if ( $this->sitetreeDB->getOption( 'exclude_childs', false, 'page', 'site_tree' ) ) {
            $this->sitetreeDB->setOption( 'exclude_children', true, 'page', 'site_tree' );
        }

        $this->sitetreeDB->deleteOption( 'exclude_childs', 'page', 'site_tree' );
    }

    /**
     * @since 1.0
     */
    private function includePageContentTypeInSitemap() {
        if ( $this->plugin->isSitemapActive( 'sitemap' ) ) {
            $this->sitetreeDB->setOption( 'page', true, 'sitemap_content_types' );
        }
    }

    /**
     * @since 1.0
     */
    private function migrateMetrics() {
        $metrics_data = $this->sitetreeDB->getNonAutoloadOption( 'metrics' );

        if ( is_array( $metrics_data ) ) {
            $this->db->setNonAutoloadOption( 'metrics', $metrics_data );
        }
    }

    /**
     * @since 1.0
     * @return bool
     */
    private function migratePingData() {
        $this->plugin->load( 'admin/ping-state.class.php' );
        $this->plugin->load( 'library/sitetree/ping-state.class.php' );

        $sitetreePingStates = $this->sitetreeDB->getNonAutoloadOption( 'pingState' );

        if (! is_array( $sitetreePingStates ) ) {
            return false;
        }

        $class_signature = '\SiteTree\PingState';

        foreach( array( 'sitemap', 'newsmap' ) as $sitemap_id ) { 
            if (! isset( $sitetreePingStates[$sitemap_id] ) ) {
                continue;
            }

            $pingState         = new PingState( $sitemap_id );
            $sitetreePingState = $sitetreePingStates[$sitemap_id];

            if ( is_object( $sitetreePingState ) && ( $sitetreePingState instanceof $class_signature ) ) {
                if ( $sitetreePingState->sitemapID() != $sitemap_id ) {
                    continue;
                }

                $pingState->setCode( $sitetreePingState->getCode() );
                $pingState->setLatestTime( $sitetreePingState->getLatestTime() );
            }

            $this->db->setNonAutoloadOption( 'pingState', $pingState, $sitemap_id );
        }

        $this->plugin->load( 'admin/ping-log-entry.class.php' );
        $this->plugin->load( 'library/sitetree/ping-log-entry.class.php' );

        $sitetree_pinging_logs = $this->sitetreeDB->getNonAutoloadOption( 'pinging_log' );

        if (! is_array( $sitetree_pinging_logs ) ) {
            return false;
        }

        $class_signature = '\SiteTree\PingLogEntry';

        foreach( array( 'sitemap', 'newsmap' ) as $sitemap_id ) { 
            if (! isset( $sitetree_pinging_logs[$sitemap_id] ) ) {
                continue;
            }

            $pinging_log          = array();
            $sitetree_pinging_log = (array) $sitetree_pinging_logs[$sitemap_id];

            foreach( $sitetree_pinging_log as $sitetreeLogEntry ) {
                if ( is_object( $sitetreeLogEntry ) && ( $sitetreeLogEntry instanceof $class_signature ) ) {
                    $logEntry = new PingLogEntry( $sitetreeLogEntry->getTime(), $sitetreeLogEntry->getPostID() );

                    $logEntry->setSearchEngineIDs( $sitetreeLogEntry->getSearchEngineIDs() );
                    $logEntry->setResponseCode( $sitetreeLogEntry->getResponseCode() );
                    $logEntry->setResponseMessage( $sitetreeLogEntry->getResponseMessage() );

                    $pinging_log[] = $logEntry;
                }
            }

            if ( $pinging_log ) {
                $this->db->setNonAutoloadOption( 'pinging_log', $pinging_log, $sitemap_id );
            }
        }

        return true;
    }

    /**
     * @since 2.0
     * @return bool
     */
    private function renameShortcodes() {
        $post_types      = get_post_types( array( 'public' => true ) );
        $post_types_list = "'" . implode( "','", $post_types ) . "'";

        $results = $this->wpdb->get_results(
            "SELECT ID, post_content
             FROM {$this->wpdb->posts}
             WHERE post_type IN ({$post_types_list}) AND post_content LIKE '%[sitetree %'"
        );

        if ( empty( $results ) ) {
            return false;
        }

        $pattern = '#(?<!\[)\[sitetree ([^\]]+)\]#';

        foreach ( $results as $post ) {
            if (! preg_match( $pattern, $post->post_content ) ) {
                continue;
            }

            $content = preg_replace( $pattern, '[permalinks-cascade $1]', $post->post_content );

            if ( $content ) {
                $this->wpdb->query(
                    "UPDATE {$this->wpdb->posts}
                     SET post_content = '{$content}' WHERE ID = {$post->ID}"
                );
            }
        }

        return true;
    }
}