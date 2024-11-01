<?php
namespace ThePermalinksCascade;

/**
 * @package The Permalinks Cascade
 * @copyright Copyright 2022 Luigi Cavalieri.
 * @license https://opensource.org/licenses/GPL-3.0 GPL v3.0
 *
 * Declares all the methods you may call on the instance of
 * SitemapBuilder received by the function you have 
 * "hooked" to the builder's action hook.
 */
interface SitemapBuilderInterface {
    /**
     * @since 1.0
     *
     * @param string $url         Absolute URL of a publicly accessible web page.
     * @param string|int $lastmod Optional. The date on which the page was last modified or the timestamp of said date.
     *                            Valid date formats at {@link https://www.php.net/manual/en/datetime.formats.php}.    
     */
    public function buildURLElement( $url, $lastmod = '' );
}


/**
 * Declares all the methods you may call on the instance of
 * SiteTreeBuilder received by the functions you have 
 * "hooked" to the builder's action hooks.
 */
interface SiteTreeBuilderInterface {
    /**
     * Returns the unique identifier of the list that is being built.
     * For Custom Post Types, it coincides with the Post Type Key.
     *
     * @since 1.0
     * @return string      
     */
    public function listID();

    /**
     * Returns the type of the content that is being processed.
     * Possible values: post, taxonomy, author.
     *
     * @since 2.0
     * @return string
     */
    public function getContentTypeFamily();
    
    /**
     * Returns 'true' or 'false' according to whether or not the builder 
     * is generating an hyper-list as a result of either a "shortcode call",
     * a "Gutenberg Block call" or a "Template Tag call".
     *
     * @since 2.0
     * @return bool
     */
    public function isDoingHyperlist();

    /**
     * Returns 'true' or 'false' according to whether or not the builder 
     * is generating an hyper-list starting from a set of Block attributes
     * associated to a Block Type registered by the plugin.
     *
     * @since 1.0
     * @return bool
     */
    public function isDoingBlock();

    /**
     * Returns 'true' or 'false' according to whether or not the builder 
     * is generating a hyper-list starting from a set of options passed 
     * as shortcode attributes.
     *
     * @since 2.0
     * @return bool
     */
    public function isDoingShortcode();

    /**
     * @since 1.0
     * @param string $string      
     */
    public function addContent( $string );
}