<?php

declare(strict_types=1);

namespace Prime\EzSiteMap\Factory;

use eZ\Publish\Core\MVC\ConfigResolverInterface;
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
    public function __construct(ConfigResolverInterface $configResolver)
    {
        $this->domain = $configResolver->getParameter('sitemap.domain', 'prime.ez_sitemap');
        $this->protocol = $configResolver->getParameter('sitemap.protocol', 'prime.ez_sitemap');
        $this->sitemaps = $configResolver->getParameter('sitemap.sitemaps_index_path', 'prime.ez_sitemap');
    }

    /**
     * @param string $webroot
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     *
     * @return \Prime\EzSiteMap\SitemapIndex
     */
    public function getSitemapIndex(string $webroot): SitemapIndex
    {
        $sitemapsDir = $webroot . '/' . $this->sitemaps;

        if (!file_exists($sitemapsDir)) {
            throw new NotFoundHttpException();
        }

        $sitemap = new SitemapIndex();

        $sitemapFiles = array_diff(scandir($sitemapsDir, SCANDIR_SORT_ASCENDING), ['..', '.']);

        if (!empty($sitemapFiles)) {
            foreach ($sitemapFiles as $sitemapFile) {
                if (is_dir($sitemapFile)) {
                    continue;
                }
                $loc = $this->protocol . '://' . $this->domain . '/' . $this->sitemaps . '/' . $sitemapFile;
                $lastModified = filemtime($sitemapsDir . '/' . $sitemapFile);
                $sitemap->addSitemap($loc, $lastModified);
            }
        }

        return $sitemap;
    }
}
