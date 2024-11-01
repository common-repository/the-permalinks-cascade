<?php
namespace ThePermalinksCascade;

/**
 * @package The Permalinks Cascade
 * @copyright Copyright 2022 Luigi Cavalieri.
 * @license https://opensource.org/licenses/GPL-3.0 GPL v3.0
 *
 * @since 1.0
 */
final class SitemapBuilder
    extends GoogleSitemapBuilder
 implements SitemapBuilderInterface {
    /**
     * @since 1.0
     */
    const SITEMAP_SLUG = 'sitemap';

    /**
     * @since 1.0
     * @var array
     */
    private $queriedPosts;

    /**
     * @since 1.0
     * @var array
     */
    private $queriedPostsIDs;

    /**
     * ID of the post whose URL element is being built.
     *
     * @since 1.0
     * @var array
     */
    private $currentID;

	/**
	 * @since 1.0
	 * @var int
	 */
	private $timezoneOffsetInSeconds;

	/**
     * @since 1.0
     *
     * @param object $plugin
     * @param object $indexer
     */
    public function __construct( $plugin, $indexer ) {
        parent::__construct( $plugin, $indexer );

        $this->timezoneOffsetInSeconds = $this->gmtOffset * HOUR_IN_SECONDS;
    }

    /**
     * @see parent::runBuildingProcess()
     * @since 1.0
     */
    protected function runBuildingProcess() {
        switch ( $this->indexer->getRequestedSitemapContentFamily() ) {
            case 'post':
                if (! $this->buildPostsElements() ) {
                    $this->buildHomePageElement();
                }
                break;

            case 'taxonomy':
                $this->buildTaxonomyPagesElements();
                break;
            
            case 'author':
                $this->buildAuthorsPagesElements();
                break;
        }
        
        /**
	     * @since 1.0
	     */
        do_action( 'tpc_is_building_sitemap', $this, $this->indexer );
    }

    /**
     * @since 1.0
     * @return int
     */
    public function getCurrentID() {
        return $this->currentID;
    }

    /**
     * @since 1.0
     * @param string $string
     */
    public function appendToOutput( $string ) {
        $this->output .= $string;
    }

    /**
     * {@inheritdoc}
     */
    public function buildURLElement( $url, $lastmod = '' ) {
        $url = esc_url( $url );

        $this->incrementItemsCounter();

        $this->output .= '<url>' . $this->lineBreak;
        $this->output .= '<loc>' . $url . '</loc>' . $this->lineBreak;
        
        if ( $lastmod ) {
        	$timestamp = ( is_int( $lastmod ) ? $lastmod : strtotime( $lastmod ) );
        	
        	if ( $timestamp ) {
        		$this->output .= '<lastmod>';
	            $this->output .= gmdate( 'Y-m-d\TH:i:s', $timestamp );
	            $this->output .= $this->timezoneOffset . '</lastmod>' . $this->lineBreak;
        	}
        }

        /**
         * @since 1.0
         */
        do_action( 'tpc_sitemap_builder_is_making_url_element', $this, $url );
        
        $this->currentID = 0;
        $this->output   .= '</url>' . $this->lineBreak;
    }

    /**
     * @since 1.0
     * @return bool
     */
    private function buildPostsElements() {
        if (! $this->queryPosts() ) {
            return false;
        }

        $this->buildHomePageElement();
        $this->buildBlogPageElement();

        foreach ( $this->queriedPosts as $post_type => $posts ) {
            $post_type_is_page  = ( $post_type == 'page' );
            
            foreach ( $posts as $post ) {
                $lastmod 		   = $post->post_modified;
                $page_has_template = false;

                $this->currentID = $post->ID;

                if ( $post_type_is_page ) {
                	$page_templates = array(
                		"page-{$post->post_name}.php",
                		"page-{$post->ID}.php",
                	);
                	$lastmod = $this->getPageTemplateLastmod( $page_templates, $lastmod );
                }

                $this->buildURLElement( get_permalink( $post ), $lastmod );
            }
        }

        unset( $this->queriedPosts, $this->queriedPostsIDs );

        return true;
    }

    /**
     * @since 1.0
     * @return bool
     */
    private function queryPosts() {
        $post_type_to_include = $this->indexer->getRequestedSitemapContentType();

        $meta_keys  = $this->db->prepareMetaKey( 'exclude_from_sitemap' );
        $meta_keys .= ',';
        $meta_keys .= $this->db->prepareMetaKey( 'is_ghost_page' );

        $query_clauses = array(
            'SELECT'          => 'p.ID, p.post_name, p.post_modified, p.post_parent, p.post_type, p.post_status',
            'FROM'            => "{$this->wpdb->posts} AS p",
            'LEFT_OUTER_JOIN' => "{$this->wpdb->postmeta} AS pm ON pm.post_id = p.ID AND pm.meta_key IN ({$meta_keys})",
            'WHERE'           => "p.post_type = '{$post_type_to_include}' AND p.post_status = 'publish' AND
                                  p.post_password = '' AND pm.post_id IS NULL",
            'ORDER_BY'        => 'p.post_modified DESC',
            'LIMIT'           => $this->buildingCapacityLeft(),
            'OFFSET'          => $this->getMysqlOffset()
        );

        /**
         * @since 1.0
         */
        $query_clauses = apply_filters( 'tpc_sitemap_builder_posts_query', $query_clauses, $post_type_to_include );

        $posts = $this->db->getResults( $query_clauses );

        if (! $posts ) {
            return false;
        }

        foreach ( $posts as $post ) {
            $post = sanitize_post( $post, 'raw' );
            
            $this->queriedPostsIDs[] = $post->ID;
            $this->queriedPosts[$post->post_type][$post->ID] = $post;

            wp_cache_add( $post->ID, $post, 'posts' );
        }

        update_meta_cache( 'post', $this->queriedPostsIDs );

        /**
         * @since 1.0
         */
        do_action( 'tpc_sitemap_builder_did_query_posts', $this->queriedPostsIDs );

        return true;
    }

    /**
     * @since 1.0
     */
    private function buildHomePageElement() {
        $front_page_id = (int) get_option( 'page_on_front' );

        if ( 
            ( 'page' == $this->indexer->getRequestedSitemapContentType() ) && 
            ( 0 === $this->indexer->getRequestedSitemapNumber() ) 
        ) {
            if ( $front_page_id ) {
                $this->currentID = $front_page_id;

                if ( isset( $this->queriedPosts['page'][$front_page_id] ) ) {
                    $frontPage = $this->queriedPosts['page'][$front_page_id];
                }
                else {
                    $frontPage = get_post( $front_page_id );
                }

                $this->buildURLElement(
                    home_url('/'),
                    $this->getPageTemplateLastmod( 'front-page.php', $frontPage->post_modified )
                ); 
            }
            else {
                $this->buildURLElement( home_url( '/' ), get_lastpostmodified( 'blog' ) );
            }
        }

        unset( $this->queriedPosts['page'][$front_page_id] );
    }

    /**
     * @since 1.0
     */
    private function buildBlogPageElement() {
        $blog_page_id = (int) get_option( 'page_for_posts' );

        if ( isset( $this->queriedPosts['page'][$blog_page_id] )  ) {
            $blogPage = $this->queriedPosts['page'][$blog_page_id];

            $this->currentID = $blog_page_id;
            
            if ( isset( $this->queriedPosts['post'] ) ) {
                $lastmod = reset( $this->queriedPosts['post'] )->post_modified;
            }
            else {
                $lastmod = $blogPage->post_modified;
            }

            $this->buildURLElement( get_permalink( $blogPage ), $lastmod );

            unset( $this->queriedPosts['page'][$blog_page_id] );
        }
    }

    /**
     * Attempts to get the modification time of a page template and
     * returns it if more recent than $default_lastmod.
     *
     * @since 1.0
     *
     * @param string|array $template_name
     * @param string $default_lastmod
     * @return int|string Timestamp or date string.
     */
    private function getPageTemplateLastmod( $template_name, $default_lastmod ) {
    	$template_filename = locate_template( $template_name );

        if ( $template_filename ) {
        	$template_mtime = filemtime( $template_filename ) + $this->timezoneOffsetInSeconds;

        	if ( $template_mtime > strtotime( $default_lastmod ) ) {
        		return $template_mtime;
        	}
        }

        return $default_lastmod;
    }

    /**
     * @since 1.0
     */
    private function buildAuthorsPagesElements() {
        $authors = $this->wpdb->get_results(
            "SELECT u.ID, u.user_nicename, MAX( p.post_modified ) AS last_post_modified
             FROM {$this->wpdb->users} AS u
             INNER JOIN {$this->wpdb->posts} AS p ON p.post_author = u.ID
             WHERE p.post_type = 'post' AND p.post_status = 'publish'
             GROUP BY p.post_author 
             ORDER BY last_post_modified DESC
             LIMIT {$this->buildingCapacityLeft()}
             OFFSET {$this->getMysqlOffset()}"
        );
        
        if (! $authors ) {
            return false;
        }

        foreach ( $authors as $author ) {
            $this->buildURLElement(
                get_author_posts_url( $author->ID, $author->user_nicename ),
                $author->last_post_modified
            );
        }
    }

    /**
     * @since 1.0
     * @return bool
     */
    private function buildTaxonomyPagesElements() {
        $term_not_in         = '';
        $taxonomy_to_include = $this->indexer->getRequestedSitemapContentType();
        $excluded_ids        = $this->db->getOption( $taxonomy_to_include, '', 'exclude_from_sitemap' );
        
        if ( $excluded_ids ) {
            $excluded_ids = implode( ',', wp_parse_id_list( $excluded_ids ) );
            $term_not_in  = 't.term_id NOT IN (' . $excluded_ids . ') AND';
        }

        $query_clauses = array(
            'SELECT'     => 't.term_id, t.slug, tt.term_taxonomy_id, tt.taxonomy, MAX(p.post_modified) AS last_modified',
            'FROM'       => "{$this->wpdb->terms} AS t",
            'INNER_JOIN' => "{$this->wpdb->term_taxonomy} AS tt USING(term_id)
                                INNER JOIN {$this->wpdb->term_relationships} AS tr USING(term_taxonomy_id)
                                INNER JOIN {$this->wpdb->posts} AS p ON p.ID = tr.object_id",
            'WHERE'      => "{$term_not_in} tt.taxonomy = '{$taxonomy_to_include}' AND p.post_status = 'publish'",
            'GROUP_BY'   => 't.term_id, tt.taxonomy',
            'ORDER_BY'   => 'last_modified DESC',
            'LIMIT'      => $this->buildingCapacityLeft(),
            'OFFSET'     => $this->getMysqlOffset()
        );

        /**
         * @since 1.0
         */
        $query_clauses = apply_filters( 'tpc_sitemap_builder_taxonomies_query', $query_clauses, $taxonomy_to_include );

        $terms = $this->db->getResults( $query_clauses );
        
        if (! $terms ) {
            return false;
        }

        $ids = array();

        foreach ( $terms as $term ) {
            $term = sanitize_term( $term, $term->taxonomy, 'raw' );
            
            $ids[] = $term->term_id;
            
            wp_cache_add( $term->term_id, $term, $term->taxonomy );
        }

        /**
         * @since 1.0
         */
        do_action( 'tpc_sitemap_builder_did_query_taxonomies', $ids );

        foreach ( $terms as $term ) {
            $this->currentID = $term->term_id;
            
            $this->buildURLElement( get_term_link( $term ), $term->last_modified );
        }
    }
}