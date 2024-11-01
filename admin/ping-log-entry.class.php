<?php
namespace ThePermalinksCascade;

/**
 * @package The Permalinks Cascade
 * @copyright Copyright 2022 Luigi Cavalieri.
 * @license https://opensource.org/licenses/GPL-3.0 GPL v3.0
 *
 * @since 1.0
 */
final class PingLogEntry {
    /**
     * @since 1.0
     * @var int
     */
    private $time;

    /**
     * @since 1.0
     * @var array
     */
    private $searchEngineIDs = array();

    /**
     * @since 1.0
     * @var int
     */
    private $postID;

    /**
     * @since 1.0
     * @var string
     */
    private $responseCode;

    /**
     * @since 1.0
     * @var string
     */
    private $responseMessage;

    /**
     * @since 1.0
     *
     * @param int $time
     * @param int $post_ID
     */
    public function __construct( $time, $post_ID ) {
        $this->time   = $time;
        $this->postID = $post_ID;
    }

    /**
     * @since 1.0
     * @return int
     */
    public function getTime() {
        return $this->time;
    }

    /**
     * @since 1.0
     * @return int
     */
    public function getPostID() {
        return $this->postID;
    }

    /**
     * @since 1.0
     * @param string $id
     */
    public function pushSearchEngineID( $id ) {
        $this->searchEngineIDs[] = $id;
    }

    /**
     * @since 1.0
     * @param array $ids
     */
    public function setSearchEngineIDs( $ids ) {
        $this->searchEngineIDs = (array) $ids;
    }

    /**
     * @since 1.0
     * @return array
     */
    public function getSearchEngineIDs() {
        return $this->searchEngineIDs;
    }

    /**
     * @since 1.0
     * @param string $code
     */
    public function setResponseCode( $code ) {
        $this->responseCode = $code;
    }

    /**
     * @since 1.0
     * @return string
     */
    public function getResponseCode() {
        return $this->responseCode;
    }

    /**
     * @since 1.0
     * @param string $message
     */
    public function setResponseMessage( $message ) {
        $this->responseMessage = $message;
    }

    /**
     * @since 1.0
     * @return string
     */
    public function getResponseMessage() {
        return $this->responseMessage;
    }
}