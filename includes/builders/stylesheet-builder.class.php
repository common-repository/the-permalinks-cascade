<?php
namespace ThePermalinksCascade;

/**
 * @package The Permalinks Cascade
 * @copyright Copyright 2022 Luigi Cavalieri.
 * @license https://opensource.org/licenses/GPL-3.0 GPL v3.0
 *
 * @since 1.0
 */
class StylesheetBuilder {
    /**
     * @since 1.0
     * @var object
     */
    protected $plugin;

    /**
     * @since 1.0
     * @var string
     */
    protected $sitemapSlug;

    /**
     * @since 1.0
     * @var string
     */
    protected $sitemapID;

    /**
     * @since 1.0
     * @var bool
     */
    protected $sitemapIsPartOfCollection = false;

    /**
     * @since 1.0
     *
     * @param object $plugin
     * @param string $sitemap_slug
     * @param string $sitemap_id
     */
    public function __construct( $plugin, $sitemap_slug, $sitemap_id ) {
        $this->plugin      = $plugin;
        $this->sitemapSlug = $sitemap_slug;
        $this->sitemapID   = $sitemap_id;
    }

    /**
     * @since 1.0
     * @param bool $true_or_false
     */
    public function setSitemapIsPartOfCollection( $true_or_false ) {
        $this->sitemapIsPartOfCollection = $true_or_false;
    }

    /**
     * @since 1.0
     */
    public function serveIndexStylesheet() {
        exit( $this->getIndexStylesheet() );
    }

    /**
     * @since 1.0
     */
    public function serveStylesheet() {
        switch ( $this->sitemapSlug ) {
            case 'sitemap':
                switch ( $this->sitemapID ) {
                    case 'image-stylesheet':
                        exit( $this->getImageSitemapStylesheet() );

                    case 'video-stylesheet':
                        exit( $this->getVideoSitemapStylesheet() );

                    default:
                        exit( $this->getSitemapStylesheet() );
                }  

            case 'newsmap':
                exit( $this->getNewsmapStylesheet() );
        }
    }

    /**
     * @since 1.0
     * @return string
     */
    protected function getIndexStylesheet() {
        switch ( $this->sitemapSlug ) {
            case 'sitemap':
                $title   = esc_html__( 'Google Sitemaps', 'the-permalinks-cascade' );
                $intro   = esc_html__( 'In this document you can find the whole collection of Google Sitemaps available for this website.', 'the-permalinks-cascade' );
                $colours = array(
                    'links'         => '#0062bb',
                    'tr_background' => '#f5f6f7'  
                );
               break;

            case 'newsmap':
                $title   = esc_html__( 'News Sitemaps', 'the-permalinks-cascade' );
                $intro   = esc_html__( 'In this document you can find the whole collection of News Sitemaps available for this website.', 'the-permalinks-cascade' );
                $colours = array(
                    'links'         => '#c16200',
                    'tr_background' => '#f7f6f5'
                );
                break;
        }

        $th_url = esc_html__( 'Sitemap URL', 'the-permalinks-cascade' );

        return <<<_XSL
<?xml version="1.0" encoding="UTF-8"?>
<!-- License and copyrights are the same as the {$this->plugin->name()} package -->
<xsl:stylesheet version="2.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sitemap="http://www.sitemaps.org/schemas/sitemap/0.9">
<xsl:output method="html" doctype-system="about:legacy-compat" encoding="UTF-8" />
<xsl:template match="/">

<html>
<head>
<meta charset="UTF-8" />
<meta name="robots" content="noindex" />

<title>{$title}</title>

<style>
{$this->getFontFaces()}

/*
 * Reset by Eric A. Meyer.
 * v2.0-modified
 * http://meyerweb.com/eric/tools/css/reset/ 
 */
html, body, h1, p, a, small, table, th, tr, td {
    border: 0;
    font-size: 100%;
    font: inherit;
    margin: 0;
    padding: 0;
    vertical-align: baseline;
}
table {
    border-collapse: collapse;
    border-spacing: 0;
}

body {
    color: #333;
    font: 14px 'Open Sans', sans-serif;
    line-height: 1;
    margin: 0 auto;
    padding: 50px 20px 10px;
    width: 500px;
}

h1 {
    color: #111;
    font-size: 56px;
    font-weight: 400;
    margin-bottom: 0.5em;
    text-align: center;
}

h1, th {
    font-family: 'Ubuntu', sans-serif;
}

p {
    font-size: 14px;
    line-height: 1.75em;
    margin: 0 auto 1em;
    text-align: center;
    width: 500px;
}

a {
    color: {$colours['links']};
    text-decoration: none;
}
a:hover { 
    text-decoration: underline;
}

table {
    margin: 50px 0;
    table-layout: fixed;
    width: 500px;
}

th, td {
    vertical-align: middle;
}

th {
    font-weight: 700;
    padding: 10px;
    text-align: left;
}

.std-column {
    width: 100px;
}

#counter-head {
    width: 45px;
}

#url-head {
    width: 100%;
}

tr {
    border-bottom: #eeedec 1px solid;
}
tr:nth-child(2n) {
    background: {$colours['tr_background']};
}

td {
    line-height: 1.5em;
    padding: 5px 10px;
    word-wrap: break-word;
    }
    td a:visited {
        color: #999;
    }

#credit-note {
    font-size: 12px;
    text-align: center;
}
</style>
</head>
<body>
    <h1>{$title}</h1>
    <p>{$intro}</p>
    <table>
        <thead>
        <tr>
            <th id="counter-head">#</th>
            <th id="url-head">{$th_url}</th>
        </tr>
        </thead>
        <tbody>
        <xsl:for-each select="sitemap:sitemapindex/sitemap:sitemap"> 
            <tr>
                <xsl:variable name="i" select="position()" />
                <td><xsl:copy-of select="\$i" /></td>

                <xsl:variable name="url" select="sitemap:loc" />
                <td><a href="{\$url}" target="{$this->plugin->id()}"><xsl:value-of select="sitemap:loc" /></a></td>
            </tr>
        </xsl:for-each>
        </tbody>
    </table>
    {$this->getCreditNote()}
<script type="text/javascript">
//<![CDATA[
var rows = document.getElementsByTagName('tr');
    
for ( var i = 1; i < rows.length; i++ ) {
    var link = rows[i].children[1].childNodes[0].firstChild;
    
    link.data = decodeURI( link.data );
}
//]]>
</script>
</body>
</html>
</xsl:template>
</xsl:stylesheet>
_XSL;
    }

    /**
     * @since 1.0
     * @return string
     */
    protected function getSitemapStylesheet() {
        /**
         * @since 1.0
         */
        $extra_xmlns = apply_filters( 'tpc_extra_xmlns_namespaces', '' );

        $title = esc_html__( 'Google Sitemap', 'the-permalinks-cascade' );

        if ( $this->sitemapIsPartOfCollection ) {
            $opening_tag = '<a href="' . esc_url( $this->plugin->sitemapURL( $this->sitemapSlug ) ) . '">';
            $message     = esc_html__( 'This document is part of %1$sa collection of sitemaps%2$s that lists all the publicly-accessible web pages of this website.', 'the-permalinks-cascade' );

            $intro = sprintf( $message, $opening_tag, '</a>' );
        }
        else {
            $intro = esc_html__( 'This document lists all the publicly-accessible web pages of this website. Although addressed to search engines, you are more than welcome to peruse it!', 'the-permalinks-cascade' );
        }
        
        /**
         * @since 1.0
         */
        $intro = apply_filters( 'tpc_sitemap_stylesheet_intro', $intro, $this->sitemapSlug );

        $extra_columns = array(
            'lastmod' => array(
                'th_tag'   => '<th id="lastmod-head">' . esc_html__( 'Last Modified', 'the-permalinks-cascade' ) . '</th>',
                'xsl_body' => '<xsl:choose>
                                <xsl:when test="sitemap:lastmod">
                                    <td><xsl:value-of select="concat(substring(sitemap:lastmod, 1,10), \'&#160;&#160;@&#160;&#160;\', substring(sitemap:lastmod, 12,5))" /></td>
                                </xsl:when>
                                <xsl:otherwise>
                                    <td>-</td>
                                </xsl:otherwise>
                               </xsl:choose>'
            ),
        );

        /**
         * @since 1.0
         */
        $extra_columns = apply_filters( 'tpc_sitemap_stylesheet_extra_columns', $extra_columns );

        $extra_th_markup = '';
        $extra_xsl_body  = '';

        foreach ( $extra_columns as $column ) {
            $extra_th_markup .= $column['th_tag'] . "\n";
            $extra_xsl_body  .= $column['xsl_body'] . "\n\n";
        }

        return <<<_XSL
<?xml version="1.0" encoding="UTF-8"?>
<!-- License and copyrights are the same as the {$this->plugin->name()} package -->
<xsl:stylesheet version="2.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sitemap="http://www.sitemaps.org/schemas/sitemap/0.9" {$extra_xmlns}>
<xsl:output method="html" doctype-system="about:legacy-compat" encoding="UTF-8" />
<xsl:template match="/">

<html>
<head>
<meta charset="UTF-8" />
<meta name="robots" content="noindex" />

<title>{$title}</title>

<style>
{$this->getFontFaces()}

/*
 * Reset by Eric A. Meyer.
 * v2.0-modified
 * http://meyerweb.com/eric/tools/css/reset/ 
 */
html, body, h1, p, a, small, table, th, tr, td {
    border: 0;
    font-size: 100%;
    font: inherit;
    margin: 0;
    padding: 0;
    vertical-align: baseline;
}
table {
    border-collapse: collapse;
    border-spacing: 0;
}

body {
    color: #333;
    font: 14px 'Open Sans', sans-serif;
    line-height: 1;
    margin: 0 auto;
    padding: 50px 20px 10px;
    width: 900px;
}

h1 {
    color: #111;
    font-size: 56px;
    font-weight: 400;
    margin-bottom: 0.5em;
    text-align: center;
}

h1, th {
    font-family: 'Ubuntu', sans-serif;
}

p {
    font-size: 14px;
    line-height: 1.75em;
    margin: 0 auto 1em;
    text-align: center;
    width: 500px;
}

a {
    color: #0062bb;
    text-decoration: none;
}
a:hover { 
    text-decoration: underline;
}

table {
    margin: 50px 0;
    table-layout: fixed;
    width: 900px;
}

th, td {
    vertical-align: middle;
}

th {
    font-weight: 700;
    padding: 10px;
    text-align: left;
}

.std-column {
    width: 100px;
}

#counter-head {
    width: 45px;
}

#url-head {
    width: 100%;
}

#lastmod-head {
    width: 140px;
}

tr {
    border-bottom: #eeedec 1px solid;
}
tr:nth-child(2n) {
    background: #f5f6f7;
}

td {
    line-height: 1.5em;
    padding: 5px 10px;
    word-wrap: break-word;
    }
    td a:visited {
        color: #999;
    }

#credit-note {
    font-size: 12px;
    text-align: center;
}
</style>
</head>
<body>
    <h1>{$title}</h1>
    <p>{$intro}</p>
    <table>
        <thead>
        <tr>
            <th id="counter-head">#</th>
            <th id="url-head">URL</th>
            {$extra_th_markup}
        </tr>
        </thead>
        <tbody>
        <xsl:for-each select="sitemap:urlset/sitemap:url"> 
            <tr>
                <xsl:variable name="i" select="position()" />
                <td><xsl:copy-of select="\$i" /></td>

                <xsl:variable name="url" select="sitemap:loc" />
                <td><a href="{\$url}" target="{$this->plugin->id()}"><xsl:value-of select="sitemap:loc" /></a></td>

                {$extra_xsl_body}
            </tr>
        </xsl:for-each>
        </tbody>
    </table>
    {$this->getCreditNote()}
<script type="text/javascript">
//<![CDATA[
var rows = document.getElementsByTagName('tr');
    
for ( var i = 1; i < rows.length; i++ ) {
    var link = rows[i].children[1].childNodes[0].firstChild;
    
    link.data = decodeURI( link.data );
}
//]]>
</script>
</body>
</html>
</xsl:template>
</xsl:stylesheet>
_XSL;
    }

    /**
     * @since 2.0
     * @return string
     */
    private function getImageSitemapStylesheet() {
        $title       = esc_html__( 'Image Sitemap', 'the-permalinks-cascade' );
        $opening_tag = '<a href="' . esc_url( $this->plugin->sitemapURL( $this->sitemapSlug ) ) . '">';
        $message     = esc_html__( 'This document is part of %1$sthe collection of sitemaps%2$s available for this website.', 'the-permalinks-cascade' );
        $intro       = sprintf( $message, $opening_tag, '</a>' );

        return <<<_XSL
<?xml version="1.0" encoding="UTF-8"?>
<!-- License and copyrights are the same as the {$this->plugin->name()} package -->
<xsl:stylesheet version="2.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sitemap="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
<xsl:output method="html" doctype-system="about:legacy-compat" encoding="UTF-8" />
<xsl:template match="/">

<html>
<head>
<meta charset="UTF-8" />
<meta name="robots" content="noindex" />

<title>{$title}</title>

<style>
{$this->getFontFaces()}

/*
 * Reset by Eric A. Meyer.
 * v2.0-modified
 * http://meyerweb.com/eric/tools/css/reset/ 
 */
html, body, h1, p, a, small, table, th, tr, td {
    border: 0;
    font-size: 100%;
    font: inherit;
    margin: 0;
    padding: 0;
    vertical-align: baseline;
}
table {
    border-collapse: collapse;
    border-spacing: 0;
}

body {
    color: #333;
    font: 14px 'Open Sans', sans-serif;
    line-height: 1;
    margin: 0 auto;
    padding: 50px 20px 10px;
    width: 1100px;
}

h1 {
    color: #111;
    font-size: 56px;
    font-weight: 400;
    margin-bottom: 0.5em;
    text-align: center;
}

h1, th {
    font-family: 'Ubuntu', sans-serif;
}

p {
    font-size: 14px;
    line-height: 1.75em;
    margin: 0 auto 1em;
    text-align: center;
    width: 500px;
}

a {
    color: #0062bb;
    text-decoration: none;
}
a:hover { 
    text-decoration: underline;
}

table {
    margin: 50px 0;
    table-layout: fixed;
    width: 1100px;
}

th, td {
    vertical-align: middle;
}

th {
    font-weight: 700;
    padding: 10px;
    text-align: left;
}

.std-column {
    width: 250px;
}

#image-head {
    width: 150px;
}

#url-head {
    width: 100%;
}

tr {
    border-bottom: #eeedec 1px solid;
}
tr:nth-child(2n) {
    background: #f5f6f7;
}

td {
    line-height: 1.5em;
    padding: 15px 10px;
    word-wrap: break-word;
    }
    td a:visited {
        color: #999;
    }

img,
.image-link {
    display: inline-block;
}

#credit-note {
    font-size: 12px;
    text-align: center;
}
</style>
</head>
<body>
    <h1>{$title}</h1>
    <p>{$intro}</p>
    <table>
        <thead>
        <tr>
            <th id="image-head">Image</th>
            <th id="url-head">Page URL</th>
            <th class="std-column">Title</th>
            <th class="std-column">Caption</th>
        </tr>
        </thead>
        <tbody>
        <xsl:for-each select="sitemap:urlset/sitemap:url"> 
            <xsl:variable name="page_url" select="sitemap:loc" />

            <xsl:for-each select="image:image">
                <tr>
                    <xsl:variable name="url" select="image:loc" />
                    <td><a href="{\$url}" class="image-link" target="{$this->plugin->id()}"><img src="{\$url}" width="150" /></a></td>
                    
                    <td><a href="{\$page_url}" target="{$this->plugin->id()}"><xsl:copy-of select="\$page_url" /></a></td>

                    <xsl:choose>
                        <xsl:when test="image:title">
                            <td><xsl:value-of select="image:title" /></td>
                        </xsl:when>
                        <xsl:otherwise>
                            <td>-</td>
                        </xsl:otherwise>
                    </xsl:choose>
                    
                    <xsl:choose>
                        <xsl:when test="image:caption">
                            <td><xsl:value-of select="image:caption" /></td>
                        </xsl:when>
                        <xsl:otherwise>
                            <td>-</td>
                        </xsl:otherwise>
                    </xsl:choose>
                </tr>
            </xsl:for-each>
        </xsl:for-each>
        </tbody>
    </table>
    {$this->getCreditNote()}
</body>
</html>
</xsl:template>
</xsl:stylesheet>
_XSL;
    }

    /**
     * @since 2.0
     * @return string
     */
    private function getVideoSitemapStylesheet() {
        $title       = esc_html__( 'Video Sitemap', 'the-permalinks-cascade' );
        $opening_tag = '<a href="' . esc_url( $this->plugin->sitemapURL( $this->sitemapSlug ) ) . '">';
        $message     = esc_html__( 'This document is part of %1$sthe collection of sitemaps%2$s available for this website.', 'the-permalinks-cascade' );
        $intro       = sprintf( $message, $opening_tag, '</a>' );

        return <<<_XSL
<?xml version="1.0" encoding="UTF-8"?>
<!-- License and copyrights are the same as the {$this->plugin->name()} package -->
<xsl:stylesheet version="2.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sitemap="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">
<xsl:output method="html" doctype-system="about:legacy-compat" encoding="UTF-8" />
<xsl:template match="/">

<html>
<head>
<meta charset="UTF-8" />
<meta name="robots" content="noindex" />

<title>{$title}</title>

<style>
{$this->getFontFaces()}

/*
 * Reset by Eric A. Meyer.
 * v2.0-modified
 * http://meyerweb.com/eric/tools/css/reset/ 
 */
html, body, h1, p, a, small, table, th, tr, td {
    border: 0;
    font-size: 100%;
    font: inherit;
    margin: 0;
    padding: 0;
    vertical-align: baseline;
}
table {
    border-collapse: collapse;
    border-spacing: 0;
}

body {
    color: #333;
    font: 14px 'Open Sans', sans-serif;
    line-height: 1;
    margin: 0 auto;
    padding: 50px 20px 10px;
    width: 1100px;
}

h1 {
    color: #111;
    font-size: 56px;
    font-weight: 400;
    margin-bottom: 0.5em;
    text-align: center;
}

h1, th {
    font-family: 'Ubuntu', sans-serif;
}

p {
    font-size: 14px;
    line-height: 1.75em;
    margin: 0 auto 1em;
    text-align: center;
    width: 500px;
}

a {
    color: #0062bb;
    text-decoration: none;
}
a:hover { 
    text-decoration: underline;
}

table {
    margin: 50px 0;
    table-layout: fixed;
    width: 1100px;
}

th, td {
    vertical-align: middle;
}

th {
    font-weight: 700;
    padding: 10px;
    text-align: left;
}

.std-column {
    width: 250px;
}

#video-head {
    width: 150px;
}

#url-head {
    width: 100%;
}

#duration-head {
    width: 70px;   
}

tr {
    border-bottom: #eeedec 1px solid;
}
tr:nth-child(2n) {
    background: #f5f6f7;
}

td {
    line-height: 1.5em;
    padding: 15px 10px;
    word-wrap: break-word;
    }
    td a:visited {
        color: #999;
    }

img,
.video-link {
    display: inline-block;
}

#credit-note {
    font-size: 12px;
    text-align: center;
}
</style>
</head>
<body>
    <h1>{$title}</h1>
    <p>{$intro}</p>
    <table>
        <thead>
        <tr>
            <th id="video-head">Video</th>
            <th id="url-head">Page URL</th>
            <th class="std-column">Title</th>
            <th class="std-column">Description</th>
            <th id="duration-head">Duration</th>
        </tr>
        </thead>
        <tbody>
        <xsl:for-each select="sitemap:urlset/sitemap:url"> 
            <xsl:variable name="page_url" select="sitemap:loc" />

            <xsl:for-each select="video:video">
                <tr>
                    <xsl:variable name="url" select="video:content_loc" />
                    <xsl:variable name="thumbnail_url" select="video:thumbnail_loc" />
                    <td><a href="{\$url}" class="video-link" target="{$this->plugin->id()}"><img src="{\$thumbnail_url}" width="150" /></a></td>
                    
                    <td><a href="{\$page_url}" target="{$this->plugin->id()}"><xsl:copy-of select="\$page_url" /></a></td>
                    <td><xsl:value-of select="video:title" /></td>
                    <td><xsl:value-of select="video:description" /></td>
                    <td><xsl:value-of select="video:duration" /></td>
                </tr>
            </xsl:for-each>
        </xsl:for-each>
        </tbody>
    </table>
    {$this->getCreditNote()}
</body>
</html>
</xsl:template>
</xsl:stylesheet>
_XSL;
    }

    /**
     * @since 1.0
     * @return string
     */
    protected function getNewsmapStylesheet() {
        $title          = esc_html__( 'News Sitemap', 'the-permalinks-cascade' );
        $no_news_notice = esc_html__( 'There is no recently published news.', 'the-permalinks-cascade' );

        if ( $this->sitemapIsPartOfCollection ) {
            $opening_tag = '<a href="' . esc_url( $this->plugin->sitemapURL( $this->sitemapSlug ) ) . '">';
            $message     = esc_html__( 'This document is part of %1$sa collection of sitemaps%2$s that lists all the news published in the last two days.', 'the-permalinks-cascade' );
            
            $intro = sprintf( $message, $opening_tag, '</a>' );
        }
        else {
            $intro = esc_html__( 'This document lists all the news published in the last two days. Although addressed to Googlebot News, you are more than welcome to peruse it!', 'the-permalinks-cascade' );
        }

        /**
         * @since 1.0
         */
        $intro = apply_filters( 'tpc_sitemap_stylesheet_intro', $intro, $this->sitemapSlug );

        $th_values = array(
            'news'      => esc_html__( 'News', 'the-permalinks-cascade' ),
            'publisher' => esc_html__( 'Publisher', 'the-permalinks-cascade' ),
            'lang'      => esc_html__( 'Language', 'the-permalinks-cascade' ),
            'pub_date'  => esc_html__( 'Publication Date', 'the-permalinks-cascade' )
        );

        return <<<_XSL
<?xml version="1.0" encoding="UTF-8"?>
<!-- License and copyrights are the same as the '{$this->plugin->name()}' package -->
<xsl:stylesheet version="2.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sitemap="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">
<xsl:output method="html" doctype-system="about:legacy-compat" encoding="UTF-8" />
<xsl:template match="/">

<html>
<head>
<meta charset="UTF-8" />
<meta name="robots" content="noindex" />

<title>{$title}</title>

<style>
{$this->getFontFaces()}

/*
 * Reset by Eric A. Meyer.
 * v2.0-modified
 * http://meyerweb.com/eric/tools/css/reset/ 
 */
html, body, h1, p, a, small, table, th, tr, td {
    border: 0;
    font-size: 100%;
    font: inherit;
    margin: 0;
    padding: 0;
    vertical-align: baseline;
}
table {
    border-collapse: collapse;
    border-spacing: 0;
}

body {
    color: #333;
    font: 14px 'Open Sans', sans-serif;
    line-height: 1;
    margin: 0 auto;
    padding: 50px 20px 10px;
    width: 900px;
}

h1 {
    color: #111;
    font-size: 56px;
    font-weight: 400;
    margin-bottom: 0.5em;
    text-align: center;
}

h1, th {
    font-family: 'Ubuntu', sans-serif;
}

p {
    font-size: 14px;
    line-height: 1.75em;
    margin: 0 auto 1em;
    text-align: center;
    width: 500px;
}

a {
    color: #c16200;
    text-decoration: none;
}
a:hover { 
    text-decoration: underline;
}

#notice,
table {
    margin: 50px 0;
}

#notice {
    background: #c1620030;
    color: #c16200;
    font-size: inherit;
    font-weight: 400;
    line-height: 38px;
    text-align: center;
    width: 100%;
}

th, td {
    vertical-align: middle;
}

th {
    font-weight: 700;
    padding: 10px;
    text-align: left;
}

#counter-head {
    min-width: 40px;
}

#news-head {
    width: 100%;
}

#publisher-head {
    min-width: 200px;
}

#language-head {
    min-width: 90px;
}

#date-head {
    min-width: 140px;
}

tr {
    border-bottom: #eeedec 1px solid;
}
tr:nth-child(2n) {
    background: #f5f6f7;
}

td {
    line-height: 1.5em;
    padding: 5px 10px;
    }
    td a:visited {
        color: #999;
    }

#credit-note {
    font-size: 12px;
    text-align: center;
}
</style>
</head>
<body>
    <h1>{$title}</h1>
    <p>{$intro}</p>
    <xsl:choose>
        <xsl:when test="sitemap:urlset/sitemap:url">
            <table>
                <thead>
                <tr>
                    <th id="counter-head">#</th>
                    <th id="news-head">{$th_values['news']}</th>
                    <th id="publisher-head">{$th_values['publisher']}</th>
                    <th id="language-head">{$th_values['lang']}</th>
                   <th id="date-head">{$th_values['pub_date']}</th>
                </tr>
                </thead>
                <tbody>
                <xsl:for-each select="sitemap:urlset/sitemap:url"> 
                    <tr>
                        <xsl:variable name="i" select="position()" />
                        <td><xsl:copy-of select="\$i" /></td>

                        <xsl:variable name="url" select="sitemap:loc" />
                        <td><a href="{\$url}" target="{$this->plugin->id()}"><xsl:value-of select="news:news/news:title" /></a></td>
                        
                        <td><xsl:value-of select="news:news/news:publication/news:name" /></td>
                        <td><xsl:value-of select="news:news/news:publication/news:language" /></td>
                        <td><xsl:value-of select="concat(substring(news:news/news:publication_date, 1,10), '&#160;&#160;@&#160;&#160;', substring(news:news/news:publication_date, 12,5))" /></td>
                    </tr>
                </xsl:for-each>
                </tbody>
            </table>
        </xsl:when>
        <xsl:otherwise>
            <p id="notice">{$no_news_notice}</p>
        </xsl:otherwise>
    </xsl:choose>
    {$this->getCreditNote()}
</body>
</html>
</xsl:template>
</xsl:stylesheet>
_XSL;
    }

    /**
     * @since 1.0
     */
    protected function getFontFaces() {
        return <<<_FONTS
@font-face {
  font-family: 'Open Sans';
  font-style: normal;
  font-weight: 400;
  src: url({$this->plugin->dirURL('resources/fonts/open-sans-regular.ttf')}) format('truetype');
}
@font-face {
  font-family: 'Ubuntu';
  font-style: normal;
  font-weight: 400;
  src: url({$this->plugin->dirURL('resources/fonts/ubuntu-regular.ttf')}) format('truetype');
}
@font-face {
  font-family: 'Ubuntu';
  font-style: normal;
  font-weight: 700;
  src: url({$this->plugin->dirURL('resources/fonts/ubuntu-bold.ttf')}) format('truetype');
}
_FONTS;
    }

    /**
     * @since 1.0
     */
    protected function getCreditNote() {
        $link = '<a href="' . esc_url( $this->plugin->pluginURI() ) . '">' . esc_html( $this->plugin->name() ) . '</a>';
        $note = sprintf( esc_html__( 'Generated by %s for WordPress.', 'the-permalinks-cascade' ), $link );

        return( '<p id="credit-note"><small>' . $note . '</small></p>' );
    }
}