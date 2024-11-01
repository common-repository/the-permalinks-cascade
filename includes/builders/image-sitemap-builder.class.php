<?php
namespace ThePermalinksCascade;

/**
 * @package The Permalinks Cascade
 * @copyright Copyright 2022 Luigi Cavalieri.
 * @license https://opensource.org/licenses/GPL-3.0 GPL v3.0
 *
 * @since 2.0
 */
final class ImageSitemapBuilder extends MediaSitemapBuilder {
    /**
     * @since 2.0
     */
    const MEDIA_TYPE = 'image';

    /**
     * @see parent::buildURLElement()
     * @since 2.0
     */
    protected function buildURLElement( $url ) {
        $images_count = 0;
        
        $this->incrementItemsCounter();

        $this->output .= '<url>' . $this->lineBreak;
        $this->output .= '<loc>' . $url . '</loc>' . $this->lineBreak;
        
        foreach ( $this->mediaElementsToProcess as $image ) {
            $title   = $image->title();
            $caption = $image->description();

            $this->output .= '<image:image>' . $this->lineBreak 
                           . '<image:loc>' . $image->url() 
                           . '</image:loc>' . $this->lineBreak;
            
            if ( $title ) {
                $this->output .= '<image:title>' . $this->prepareAttribute( $title )
                               . '</image:title>' . $this->lineBreak;
            }
            
            if ( $caption ) {
                $this->output .= '<image:caption>' . $this->prepareAttribute( $caption, 160 )
                               . '</image:caption>' . $this->lineBreak;
            }
            
            $this->output .= '</image:image>' . $this->lineBreak;

            $images_count += 1;
        }

        $this->numberOfMedia += $images_count;
        
        $this->output .= '</url>' . $this->lineBreak;
    }
}