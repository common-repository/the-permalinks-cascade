<?php
namespace SiteTree;

/**
 * @package SiteTree
 * @copyright Copyright 2022 Luigi Cavalieri.
 * @license https://opensource.org/licenses/GPL-3.0 GPL v3.0
 *
 * @since 1.0
 */
final class PingState {
    /**
     * @since 1.0
     * @var string
     */
    private $sitemapID;

    /**
     * Status code that identifies the overall state 
     * of the instance.
     *
     * @since 1.0
     * @var string
     */
    private $code = 'no_pings_yet';
    
    /**
     * Timestamp of the latest ping event.
     *
     * @since 1.0
     * @var int
     */
    private $latestTime = 0;

    /**
     * @since 1.0
     * @param string $sitemap_id
     */
    public function __construct( $sitemap_id ) {
        $this->sitemapID = $sitemap_id;
    }

    /**
     * @since 1.0
     *
     * @param string $sitemap_id
     * @return string
     */
    public function setSitemapID( $sitemap_id ) {
        $this->sitemapID = $sitemap_id;
    }

    /**
     * @since 1.0
     * @return string
     */
    public function sitemapID() {
        return $this->sitemapID;
    }

    /**
     * @since 1.0
     * @param string $code
     */
    public function setCode( $code ) {
        $this->code = $code;
    }

    /**
     * @since 1.0
     * @return string
     */
    public function getCode() {
        return $this->code; 
    }

    /**
     * @since 1.0
     */
    public function registerTime() {
        $this->latestTime = time();
    }

    /**
     * @since 1.0
     * @param int $time
     */
    public function setLatestTime( $time ) {
        $this->latestTime = $time;
    }
    
    /**
     * @since 1.0
     * return int
     */
    public function getLatestTime() {
        return $this->latestTime;
    }

    /**
     * Utility method called by the Upgrader.
     * Accesses the deprecated property {@see $times}.
     *
     * @since 1.0
     */
    public function resetTimes() {
        $this->latestTime = max( $this->times );
        $this->times      = array();
    }

    /**
     * @since 1.0
     *
     * @param string $post_ID
     * @param array $responses
     */
    public function update( $post_ID, $responses ) {
        $prev_response_was_failed = false;
        
        foreach ( $responses as $search_engine_id => $response ) {
            if ( $response['status'] === '200' ) {
                $this->latestTime = $response['time'];

                if (! $prev_response_was_failed ) {
                    $this->code = 'succeeded';
                }
            }
            elseif ( $prev_response_was_failed ) {
                $this->code = 'failed';
            }
            else {
                $prev_response_was_failed = true;
                
                $this->code = 'no_' . $search_engine_id;
            }
        }
    }
}