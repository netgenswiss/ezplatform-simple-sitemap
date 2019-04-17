<?php

namespace Prime\EzSiteMap\Factory;

use Prime\EzSiteMap\Sitemap\SitemapIndex;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SitemapFactory
{
    /**
     * @var string
     */
    protected $sitemaps;

    /**
     * @var string
     */
    protected $domain;

    /**
     * @var string
     */
    protected $protocol;

    /**
     * SitemapFactory constructor.
     *
     * @param string $domain
     * @param string $protocol
     * @param string $sitemaps
     */
    public function __construct(string $domain, string $protocol, string $sitemaps)
    {
        $this->domain = $domain;
        $this->protocol = $protocol;
        $this->sitemaps = $sitemaps;
    }

    /**
     * @param string $webroot
     *
     * @return \Prime\EzSiteMap\SitemapIndex
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function getSitemapIndex(string $webroot): SitemapIndex
    {
        $sitemapsDir = $webroot . '/' . $this->sitemaps;

        if(!file_exists($sitemapsDir)){
            throw new NotFoundHttpException();
        }

        $sitemap = new SitemapIndex();

        $sitemapFiles = array_diff(scandir( $sitemapsDir, SCANDIR_SORT_ASCENDING), ['..', '.']);

        if(!empty($sitemapFiles)){
            foreach($sitemapFiles as $sitemapFile){
                if(is_dir($sitemapFile)){
                    continue;
                }
                $loc = $this->protocol . "://" . $this->domain . '/' . $this->sitemaps . '/' . $sitemapFile;
                $lastModified = filemtime($sitemapsDir . '/' . $sitemapFile);
                $sitemap->addSitemap($loc, $lastModified);
            }
        }

        return $sitemap;
    }
}
