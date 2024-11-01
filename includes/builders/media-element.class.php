<?php
namespace ThePermalinksCascade;

/**
 * @package The Permalinks Cascade
 * @copyright Copyright 2022 Luigi Cavalieri.
 * @license https://opensource.org/licenses/GPL-3.0 GPL v3.0
 *
 * @since 2.0
 */
final class MediaElement {
    /**
     * @since 2.0
     * @var int
     */
    private $ID;

    /**
     * @since 2.0
     * @var int
     */
    private $attachmentData;

    /**
     * @since 2.0
     * @var string
     */
    private $url;

    /**
     * @since 2.0
     * @var string
     */
    private $duration = '';

    /**
     * @since 2.0
     */
    public function __construct( $attachment ) {
        $this->attachmentData = $attachment;
        $this->ID             = $attachment->ID;
    }

    /**
     * @since 2.0
     * @return object
     */
    public function getAttachmentData() {
        return $this->attachmentData;
    }

    /**
     * @since 2.0
     * @return string
     */
    public function url() {
        if ( $this->ID && !$this->url ) {
            $this->url = wp_get_attachment_url( $this->ID );
        }

        return $this->url;
    }

    /**
     * @since 2.0
     * @return string
     */
    public function title() {
        return $this->attachmentData->post_title;
    }

    /**
     * @since 2.0
     * @return string
     */
    public function description() {
        $description = '';
        
        if ( $this->attachmentData->post_excerpt ) {
            $description = $this->attachmentData->post_excerpt;
        }
        else {
            $description = $this->attachmentData->post_content;
        }

        return $description;
    }

    /**
     * @since 2.0
     * @return string
     */
    public function duration() {
        if ( $this->ID && !$this->duration ) {
            $meta = wp_get_attachment_metadata( $this->ID );

            if ( isset( $meta['length_formatted'] ) && preg_match( '/^[:0-9]{4,8}$/', $meta['length_formatted'] ) ) {
                $this->duration = $meta['length_formatted'];
            }
            else {
                $this->duration = '0:00';
            }
        }

        return $this->duration;
    }
}