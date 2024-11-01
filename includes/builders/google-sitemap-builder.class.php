<?php
namespace ThePermalinksCascade;

/**
 * @package The Permalinks Cascade
 * @copyright Copyright 2022 Luigi Cavalieri.
 * @license https://opensource.org/licenses/GPL-3.0 GPL v3.0
 *
 * @since 1.0
 */
abstract class GoogleSitemapBuilder extends BuilderCore {
    /**
     * @since 1.0
     * @var object
     */
    public $indexer;

    /**
     * @since 1.0
     * @var array
     */
    protected $the_query;

    /**
     * @since 1.0
     * @var int
     */
    protected $gmtOffset;

    /**
     * The timezone offset expressed in hours (ex. +02:00).
     *
     * @since 1.0
     * @var string
     */
    protected $timezoneOffset;
    
    /**
     * @since 1.0
     * @var string
     */
    protected $siteCharset;
    
    /**
     * @since 1.0
     * @var string
     */
    protected $lineBreak = '';

    /**
     * @since 1.0
     *
     * @param object $plugin
     * @param object $indexer
     */
    public function __construct( $plugin, $indexer ) {
        parent::__construct( $plugin );

        $this->indexer          = $indexer;
        $this->siteCharset      = get_bloginfo( 'charset' );
        $this->gmtOffset        = (int) get_option( 'gmt_offset' );
        $this->timezoneOffset   = sprintf( '%+03d:00', $this->gmtOffset );
        $this->buildingCapacity = $indexer->getMaxPermalinksPerSitemap();

        if ( WP_DEBUG ) {
            $this->lineBreak = "\n";
        }
    }

    /**
     * @since 1.0
     * @return int
     */
    protected function getMysqlOffset() {
        $requested_sitemap_number = $this->indexer->getRequestedSitemapNumber();

        if ( $requested_sitemap_number > 1 ) {
            return ( ( $requested_sitemap_number - 1 ) * $this->indexer->getMaxPermalinksPerSitemap() );
        }

        return 0;
    }

    /**
     * @since 1.0
     *
     * @param string $attribute
     * @param int $max_length
     * @return string
     */
    protected function prepareAttribute( $attribute, $max_length = 70 ) {
        $attribute = html_entity_decode( $attribute, ENT_QUOTES, $this->siteCharset );
        $attribute = preg_replace( '/[\n\r\t\040]+/', ' ', strip_tags( $attribute ) );
        $attribute = utilities\truncate_sentence( $attribute, $max_length );
        
        return htmlspecialchars( $attribute, ENT_QUOTES );
    }
}