<?php
/**
 * @package The Permalinks Cascade
 * @copyright Copyright 2022 Luigi Cavalieri.
 * @license https://opensource.org/licenses/GPL-3.0 GPL v3.0
 */

/**
 * Returns a Hyper-list for a given Content Type.
 *
 * @since 2.0
 *
 * @param string $type
 * @param array $arguments
 * @return string
 */
function tpc_get_hyperlist( $type, $arguments = array() ) {
    if ( !( $type && is_string( $type ) ) ) {
        _doing_it_wrong(
            __FUNCTION__,
            sprintf(
                __( 'The first parameter passed to %s should be a string representing the Content Type.', 'the-permalinks-cascade' ),
                '<code>tpc_get_hyperlist()</code>'
            ),
            '5.2.0'
        );
    }

    $plugin              = \SiteTree\Core::invoke();
    $hyperlistController = $plugin->invokeGlobalObject( 'HyperlistController' );

    return $hyperlistController->getHyperlist( $type, $arguments );
}