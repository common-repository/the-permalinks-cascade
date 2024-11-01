<?php
namespace ThePermalinksCascade;

/**
 * @package The Permalinks Cascade
 * @copyright Copyright 2022 Luigi Cavalieri.
 * @license https://opensource.org/licenses/GPL-3.0 GPL v3.0
 * ************************************************************ */


if ( defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	include( 'library/base-plugin.class.php' );
    include( 'includes/core.class.php' );

    global $wpdb;

    Core::launch( __DIR__ );

    $db = Core::invoke()->db();

    if ( $db->getOption( 'deep_uninstal' ) ) {
        $taxonomy_name = $db->prefixDBKey( 'topic' );

        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '{$db->escapedDBKeyPrefix()}%'" );
        $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '{$db->escapedMetaKeyPrefix()}%'" );
        $wpdb->query( 
            "DELETE FROM {$wpdb->terms} AS t 
             WHERE t.term_id IN (
                SELECT tt.term_id FROM {$wpdb->term_taxonomy} AS tt
                WHERE tt.taxonomy = '{$taxonomy_name}'
             )"
        );
        $wpdb->query( 
            "DELETE FROM {$wpdb->term_relationships} AS tr 
             WHERE tr.term_taxonomy_id IN (
                SELECT tt.term_taxonomy_id FROM {$wpdb->term_taxonomy} AS tt
                WHERE tt.taxonomy = '{$taxonomy_name}'
             )"
        );
        $wpdb->query( "DELETE FROM {$wpdb->term_taxonomy} WHERE taxonomy = '{$taxonomy_name}'" );
    }

    delete_option( $db->optionsID() );
}