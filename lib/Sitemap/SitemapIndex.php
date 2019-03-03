<?php

namespace Prime\EzSiteMap;

use DOMDocument;

/**
 * Class SitemapIndex
 * @package Prime\eZ\Sitemap
 */
class SitemapIndex
{
    /**
     * @var \DOMElement
     */
    protected $sitemapIndex;

    /**
     * @var \DOMDocument
     */
    protected $doc;

    /**
     * 
     */
    public function __construct()
    {
        $this->doc = new DOMDocument("1.0", 'UTF-8');
        $this->sitemapIndex = $this->doc->createElement('sitemapindex');
        $this->sitemapIndex->setAttribute( "xmlns", "http://www.sitemaps.org/schemas/sitemap/0.9" );
    }

    /**
     *
     */
    public function addSitemap( string $loc, int $lastModTimestamp )
    {
        $sitemap = $this->doc->createElement( 'sitemap' );
        $loc = $this->doc->createElement( 'loc', $loc );
        $lastMod = $this->doc->createElement( 'lastmod', date('c', $lastModTimestamp));

        $sitemap->appendChild( $loc );
        $sitemap->appendChild( $lastMod );

        $this->sitemapIndex->appendChild( $sitemap );
    }

    /**
     * @return string
     */
    public function export()
    {
        $this->doc->appendChild($this->sitemapIndex);
        $this->doc->formatOutput = true;
        return $this->doc->saveXML();
    }
}
