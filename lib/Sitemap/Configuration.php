<?php

declare(strict_types=1);

namespace Prime\EzSiteMap\Sitemap;

use eZ\Publish\Core\MVC\ConfigResolverInterface;

class Configuration
{
    /**
     * @var \eZ\Publish\Core\MVC\ConfigResolverInterface
     */
    protected $configResolver;

    public function __construct(ConfigResolverInterface $configResolver)
    {
        $this->configResolver = $configResolver;
    }

    public function getDomain(): string
    {
        return $this->configResolver
            ->getParameter('sitemap.domain', 'prime.ez_sitemap');
    }

    public function getProtocol(): string
    {
        return $this->configResolver
            ->getParameter('sitemap.protocol', 'prime.ez_sitemap');
    }

    /**
     * Container folder for multiple sitemap files when we have a sitemap index in sitemap.xml.
     *
     * @return string
     */
    public function getSitemapsIndexPath(): string
    {
        return $this->configResolver
            ->getParameter('sitemap.sitemaps_index_path', 'prime.ez_sitemap');
    }

    public function getSharedMaxAge(): int
    {
        return $this->configResolver
            ->getParameter('sitemap.shared_max_age', 'prime.ez_sitemap');
    }

    public function getContentTypeList(): array
    {
        return $this->configResolver
            ->getParameter('sitemap.content_type_list', 'prime.ez_sitemap');
    }

    public function getMaxItemsPerPage(): int
    {
        return $this->configResolver
            ->getParameter('sitemap.max_items_per_sitemap', 'prime.ez_sitemap');
    }
}
