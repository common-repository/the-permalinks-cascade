<?php
/**
 * Plugin Name: The Permalinks Cascade
 * Plugin URI: https://luigicavalieri.com/the-permalinks-cascade/
 * Description: Sitemaps, Hyper-lists and Beyond.
 * Version: 2.2
 * Requires: 5.8
 * Author: Luigi Cavalieri
 * Author URI: https://luigicavalieri.com
 * License: GPLv3
 * License URI: https://opensource.org/licenses/GPL-3.0
 *
 *
 * @package The Permalinks Cascade
 * @version 2.2
 * @copyright Copyright 2022 Luigi Cavalieri.
 * @license https://opensource.org/licenses/GPL-3.0 GPL v3.0
 * 
 * 
 * Copyright 2022 Luigi Cavalieri.
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * ************************************************************************* */

if (! defined( 'ABSPATH' ) ) {
    exit;
}

if ( 
    in_array( 'the-permalinks-cascade-pro/the-permalinks-cascade-pro.php', (array) get_option( 'active_plugins' ) ) || 
    ( is_multisite() && isset( get_site_option( 'active_sitewide_plugins' )['the-permalinks-cascade-pro/the-permalinks-cascade-pro.php'] ) ) 
) {
    if ( is_admin() ) {
        exit( 'The Permalinks Cascade cannot be activated while The Permalinks Cascade Pro is running.' );
    }
}
else {
    include( 'library/base-plugin.class.php' );
    include( 'includes/core.class.php' );
    include( 'includes/template-tags.php' );

    \ThePermalinksCascade\Core::launch( __DIR__ );
}