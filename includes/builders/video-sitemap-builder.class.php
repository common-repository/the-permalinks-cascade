<?php
namespace ThePermalinksCascade;

/**
 * @package The Permalinks Cascade
 * @copyright Copyright 2022 Luigi Cavalieri.
 * @license https://opensource.org/licenses/GPL-3.0 GPL v3.0
 *
 * @since 2.0
 */
final class VideoSitemapBuilder extends MediaSitemapBuilder {
    /**
     * @since 2.0
     */
    const MEDIA_TYPE = 'video';

    /**
     * @since 2.0
     * @var string
     */
    private $fallbackThumbnailURL;

    /**
     * @see parent::__construct()
     * @since 2.0
     */
    public function __construct( $plugin, $indexer ) {
        parent::__construct( $plugin, $indexer );

        $this->fallbackThumbnailURL = $this->db->getOption( 'fallback_video_thumb_url', 
                                                            $plugin->dirURL( 'resources/thumbnail.png' ) );
        $this->fallbackThumbnailURL = esc_url( $this->fallbackThumbnailURL );
    }

    /**
     * @see parent::buildURLElement()
     * @since 2.0
     */
    protected function buildURLElement( $url ) {
        $videos_count  = 0;
        
        $this->incrementItemsCounter();

        $this->output .= '<url>' . $this->lineBreak;
        $this->output .= '<loc>' . $url . '</loc>' . $this->lineBreak;
        
        foreach ( $this->mediaElementsToProcess as $video ) {
            $thumbnail_url = get_the_post_thumbnail_url( $video->getAttachmentData() );

            if (! $thumbnail_url ) {
                $thumbnail_url = $this->fallbackThumbnailURL;
            }

            $this->output .= '<video:video>' . $this->lineBreak 
                           . '<video:thumbnail_loc>' . $thumbnail_url . '</video:thumbnail_loc>' . $this->lineBreak 
                           . '<video:title>' . $this->prepareAttribute( $video->title() ) . '</video:title>' . $this->lineBreak 
                           . '<video:description>' . $this->prepareAttribute( $video->description(), 1000 ) 
                           . '</video:description>' . $this->lineBreak
                           . '<video:content_loc>' . $video->url() . '</video:content_loc>' . $this->lineBreak
                           . '<video:duration>' . $video->duration() . '</video:duration>' . $this->lineBreak
                           . '</video:video>' . $this->lineBreak;

            $videos_count += 1;
        }

        $this->numberOfMedia += $videos_count;
        
        $this->output .= '</url>' . $this->lineBreak;
    }
}