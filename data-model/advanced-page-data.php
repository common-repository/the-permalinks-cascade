<?php
namespace ThePermalinksCascade;

/**
 * @package The Permalinks Cascade
 * @copyright Copyright 2022 Luigi Cavalieri.
 * @license https://opensource.org/licenses/GPL-3.0 GPL v3.0
 * ************************************************************ */

if (! defined( 'ABSPATH' ) ) {
    exit;
}

if ( $this->plugin->isSitemapActive( 'sitemap' ) ) {
    $seo_section = new Section( __( 'Google Sitemaps and SEO', 'the-permalinks-cascade' ) );
    $seo_section->addField(
        new Fieldset( __( 'Add to the robots.txt file', 'the-permalinks-cascade' ), '', '', array(
            new Field( 'generate_disallow_rules', 'Checkbox', 'bool', '',
                       sprintf( __( 'A %s rule for each permalink excluded from the Sitemaps.', 'the-permalinks-cascade' ), '<code>Disallow</code>' ) ),
            new Field( 'add_sitemap_url_to_robots', 'Checkbox', 'bool', '', __( 'The permalink of the Sitemap.', 'the-permalinks-cascade' ) )
        ))
    );

    $this->registerSection( $seo_section );

    $exclude_fields = array();
    $taxonomies     = get_taxonomies( array( 'public' => true ), 'objects' );

    unset( $taxonomies['post_format'] );

    foreach ( $taxonomies as $taxonomy ) {
    	if ( $this->plugin->isContentTypeIncluded( $taxonomy->name, 'sitemap' ) ) {
            $exclude_fields[] = new Field( $taxonomy->name, 'TextField', 'list_of_ids', 
                                           sprintf( __( 'Exclude %s', 'the-permalinks-cascade' ), strtolower( $taxonomy->label ) ), 
                                           __( 'Comma-separated list of IDs.', 'the-permalinks-cascade' ), '' );
    	}
    }

    if ( $exclude_fields ) {
       $this->registerSection( new Section( '', 'exclude_from_sitemap', $exclude_fields ) ); 
    }

    $video_section = new Section( __( 'Video Sitemaps', 'the-permalinks-cascade' ) );
    $video_section->addField( new Field( 'fallback_video_thumb_url', 'TextField', 'url',
                                         __( 'Fallback thumbnail URL', 'the-permalinks-cascade' ), 
                                         __( 'Use an image larger than 60x30 pixels.', 'the-permalinks-cascade' ),
                                         $this->plugin->dirURL( 'resources/thumbnail.png' ) ) );

    $this->registerSection( $video_section );
}

$general_section = new Section( __( 'General Settings', 'the-permalinks-cascade' ) );
$general_section->addField(
    new Field( 'deep_uninstal', 'Checkbox', 'bool', __( 'On uninstalling', 'the-permalinks-cascade' ),
               __( 'Erase from the database all data saved by the plugin. General settings are deleted anyway.', 'the-permalinks-cascade' ) )
);

$this->registerSection( $general_section );