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

$this->registerGlobalObjectResources( 'DataController', array(
    'data-model/data-model-classes.php',
    'data-model/data-controller.class.php'
));

$this->registerGlobalObjectResources( 'AdminController', array( 'admin/admin-controller.class.php' ) );

$this->registerGlobalObjectResources( 'EditBoxController', array( 'admin/edit-box-controller.class.php' ) );

$this->registerGlobalObjectResources( 'PingController', array(
    'admin/ping-state.class.php',
    'admin/ping-log-entry.class.php',
    'admin/ping-controller.class.php'
));

$this->registerGlobalObjectResources( 'SiteTreeBuilder', array(
    'includes/builders/builder-core.class.php',
    'includes/builders/builders-interfaces.php',
    'includes/builders/site-tree-builder.class.php'
));

$this->registerGlobalObjectResources( 'HyperlistController', array( 'includes/hyperlist-controller.class.php' ) );