<?php
namespace ThePermalinksCascade;

/**
 * @package The Permalinks Cascade
 * @copyright Copyright 2022 Luigi Cavalieri.
 * @license https://opensource.org/licenses/GPL-3.0 GPL v3.0
 *
 * 
 *
 * @since 1.0
 */
abstract class BuilderCore {
    /**
     * @since 1.0
     */
    const SITEMAP_SLUG = '';

    /**
     * @since 1.0
     * @var object
     */
    protected $plugin;

    /**
	 * @since 1.0
	 * @var object
	 */
	protected $db;

    /**
     * Reference to the global $wpdb object.
     *
     * @since 1.0
     * @var object
     */
    protected $wpdb;

    /**
     * @since 1.0
     * @var string
     */
    protected $output = '';

    /**
     * Total number of items that have been created.
     *
     * The nature of the items is determined by 
     * the nature of the parent class.
     *
     * @since 1.0
     * @var int
     */
    protected $numberOfItems = 0;

    /**
     * Maximum number of items that the builder can create.
     *
     * @since 1.0
     * @var int
     */
    protected $buildingCapacity;
	
	/**
     * Collection of information about the 
     * building process.
     *
     * @since 1.0
     * @var array
     */
    protected $metrics = array();
	
	/**
	 * @since 1.0
     *
	 * @param object $plugin
     * @param object $delegate
     */
    public function __construct( $plugin ) {
        global $wpdb;
        
        $this->plugin   = $plugin;
        $this->db       = $plugin->db();
        $this->wpdb     = $wpdb;
    }

    /**
     * @since 1.0
     * @deprecated Since version 6.0
     *
     * @return string
     */
    public function sitemapID() {
        _deprecated_function( __METHOD__, '6.0', 'BuilderCore::sitemapSlug()' );
        
        return $this->sitemapSlug();
    }

    /**
     * @since 1.0
     * @return string
     */
    public function sitemapSlug() {
        return static::SITEMAP_SLUG;
    }

    /**
     * @since 1.0
     * @return int
     */
    public function getNumberOfItems() {
        return $this->numberOfItems;
    }

    /**
     * @see $metrics
     * @since 1.0
     *
     * @return array
     */
    public function getMetrics() {
        return $this->metrics;
    }

    /**
     * Starts the buiding process and returns its product.
     *
     * @since 1.0
     * @return string
     */ 
    public function &build() {
        $this->startCounters();
        $this->runBuildingProcess();
        $this->stopCounters();

        return $this->output;
    }

    /**
     * @since 1.0
     */
    abstract protected function runBuildingProcess();

    /**
     * @since 1.0
     */
    protected function startCounters() {
        $this->metrics['runtime']     = -microtime( true );
        $this->metrics['num_queries'] = -get_num_queries();
    }

    /**
     * @since 1.0
     */
    protected function stopCounters() {
        $this->metrics['num_items']    = $this->numberOfItems;
        $this->metrics['num_queries'] += get_num_queries();
        $this->metrics['runtime']      = round( $this->metrics['runtime'] + microtime(true), 3 );
    }

    /**
     * @see $numberOfItems
     * @since 1.0
     */
    public function incrementItemsCounter() {
        $this->numberOfItems += 1;
    }

    /**
     * Returns the maximum number of items that the builder
     * can still create.
     *
     * @since 1.0
     * @return int
     */ 
    protected function buildingCapacityLeft() {
        return $this->buildingCapacity - $this->numberOfItems;
    }
}