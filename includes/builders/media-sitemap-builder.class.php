<?php
namespace ThePermalinksCascade;

/**
 * @package The Permalinks Cascade
 * @copyright Copyright 2022 Luigi Cavalieri.
 * @license https://opensource.org/licenses/GPL-3.0 GPL v3.0
 *
 * @since 2.0
 */
abstract class MediaSitemapBuilder extends GoogleSitemapBuilder {
    /**
     * @since 2.0
     */
    const MEDIA_TYPE = '';

    /**
     * @since 2.0
     * @var int
     */
    protected $numberOfMedia = 0;

    /**
     * @since 2.0
     * @var array
     */
    protected $queriedPosts;

    /**
     * @since 2.0
     * @var array
     */
    protected $queriedPostsIDs;

    /**
     * @since 2.0
     * @var array
     */
    protected $queriedMedia;

    /**
     * @since 2.0
     * @var array Reference.
     */
    protected $mediaElementsToProcess;

    /**
     * @see parent::getMetrics()
     * @since 2.0
     */
    public function getMetrics() {
        $this->metrics['num_media'] = $this->numberOfMedia;

        return $this->metrics;
    }

    /**
     * @see parent::runBuildingProcess()
     * @since 2.0
     */
    protected function runBuildingProcess() {
        if (! $this->queryPosts() ) {
            return false;
        }

        $this->queryMedia();
        
        foreach ( $this->queriedPosts as $post ) {
            $this->mediaElementsToProcess = array();
            
            if ( isset( $this->queriedMedia[$post->ID] ) ) {
                $this->mediaElementsToProcess = &$this->queriedMedia[$post->ID];
            }

            $this->buildURLElement( esc_url( get_permalink( $post ) ) );
        }

        unset( $this->queriedPosts, $this->queriedPostsIDs, $this->queriedMedia );

        return true;
    }

    /**
     * @since 2.0
     * @return bool
     */
    protected function queryPosts() {
        $post_types_list = $this->getPostTypesList();

        if (! $post_types_list ) {
            return false;
        }

        $meta_keys  = $this->db->prepareMetaKey( 'exclude_from_sitemap' );
        $meta_keys .= ',';
        $meta_keys .= $this->db->prepareMetaKey( 'is_ghost_page' );

        $media_type = static::MEDIA_TYPE;

        $query_clauses = array(
            'SELECT'          => 'p.ID, p.post_name, p.post_parent, p.post_type, p.post_status',
            'FROM'            => "{$this->wpdb->posts} AS p",
            'LEFT_OUTER_JOIN' => "{$this->wpdb->postmeta} AS pm ON p.ID = pm.post_id AND pm.meta_key IN ({$meta_keys})",
            'WHERE'           => "p.ID IN (
                                    SELECT t.post_parent
                                    FROM {$this->wpdb->posts} AS t
                                    WHERE t.post_type = 'attachment' AND t.post_mime_type LIKE '{$media_type}/%' 
                                  ) AND p.post_type IN ({$post_types_list}) AND p.post_status = 'publish' AND 
                                        p.post_password = '' AND pm.post_id IS NULL",
            'LIMIT'           => "{$this->buildingCapacityLeft()}",
            'OFFSET'          => "{$this->getMysqlOffset()}"
        );

        /**
         * @since 2.0
         */
        $query_clauses = apply_filters( 'tpc_media_sitemap_builder_posts_query', $query_clauses, $post_types_list );

        $posts = $this->db->getResults( $query_clauses );

        if (! $posts ) {
            return false;
        }

        foreach ( $posts as $post ) {
            $post = sanitize_post( $post, 'raw' );
            
            $this->queriedPosts[]    = $post;
            $this->queriedPostsIDs[] = $post->ID;

            wp_cache_add( $post->ID, $post, 'posts' );
        }

        update_meta_cache( 'post', $this->queriedPostsIDs );

        return true;
    }

    /**
     * @since 2.0
     * @return string
     */
    protected function getPostTypesList() {
        $post_types_list = $this->indexer->getPostTypesList();

        if (! $post_types_list ) {
            $post_types_list = '';
            $post_types      = get_post_types( array( 'public' => true ) );
        
            foreach ( $post_types as $post_type ) {
                if ( $this->plugin->isContentTypeIncluded( $post_type, 'sitemap' ) ) {
                    $post_types_list .= "'" . $post_type . "',";
                }
            }

            // Removes the trailing comma from the string.
            $post_types_list = substr( $post_types_list, 0, -1 );
        }

        return $post_types_list;
    }

    /**
     * @since 2.0
     */
    protected function queryMedia() {
        $media_type  = static::MEDIA_TYPE;
        $list_of_ids = implode( ',', $this->queriedPostsIDs );
        
        $attachments = $this->wpdb->get_results(
            "SELECT p.ID, p.post_title, p.post_content, p.post_excerpt, p.post_parent, p.post_type
             FROM {$this->wpdb->posts} AS p
             WHERE p.post_parent IN ({$list_of_ids}) AND p.post_type = 'attachment' AND
                   p.post_mime_type LIKE '{$media_type}/%' AND
                   p.post_modified >= COALESCE( 
                        ( SELECT p2.post_modified
                          FROM {$this->wpdb->posts} AS p2
                          WHERE p2.post_type = 'attachment' AND
                                p2.post_mime_type LIKE '{$media_type}/%' AND
                                p2.post_parent = p.post_parent
                          ORDER BY p2.post_modified DESC
                          LIMIT 999, 1
                        ),
                        p.post_modified
                   )
             ORDER BY p.post_modified DESC"
        );
        
        if ( $attachments ) {
            $attachmentsIDs = array();

            foreach ( $attachments as $attachment ) {
                $attachment = sanitize_post( $attachment, 'raw' );
                
                $post_id          = $attachment->post_parent;
                $attachmentsIDs[] = $attachment->ID;

                wp_cache_add( $attachment->ID, $attachment, 'posts' );

                $this->queriedMedia[$post_id][] = new MediaElement( $attachment );
            }

            update_meta_cache( 'post', $attachmentsIDs );
        }
    }

    /**
     * @since 2.0
     * @param string $url
     */
    abstract protected function buildURLElement( $url );
}