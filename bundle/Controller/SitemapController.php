<?php

namespace Prime\Bundle\EzSiteMapBundle\Controller;

use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Netgen\Bundle\EzPlatformSiteApiBundle\Controller\Controller;
use Prime\EzSiteMap\Factory\SitemapFactory;
use Symfony\Component\HttpFoundation\Response;

final class SitemapController extends Controller
{
    /**
     * @var string
     */
    protected $webDir;

    /**
     * @var \Prime\EzSiteMap\Factory\SitemapFactory
     */
    protected $sitemapFactory;

    /**
     * @var \eZ\Publish\Core\MVC\ConfigResolverInterface
     */
    private $configResolver;

    /**
     * SitemapController constructor.
     *
     * @param \eZ\Publish\Core\MVC\ConfigResolverInterface $configResolver
     * @param \Prime\EzSiteMap\Factory\SitemapFactory $sitemapFactory
     * @param string $webDir
     */
    public function __construct(ConfigResolverInterface $configResolver, SitemapFactory $sitemapFactory, string $webDir)
    {
        $this->webDir = $webDir;
        $this->sitemapFactory = $sitemapFactory;
        $this->configResolver = $configResolver;
    }

    /**
     * Returns valid response with sitemap contents
     * or throws 404
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function getSitemap(): Response
    {
        $sitemap = $this->sitemapFactory->getSitemapIndex($this->webDir);

        $response = new Response($sitemap);
        $response->headers->set('Content-Type','text/xml');
        $response->setCharset('utf-8');
        $response->setPublic();
        $response->setVary('Accept-Encoding');
        $response->setSharedMaxAge(
            $this->configResolver->getParameter('sitemap.shared_max_age', 'prime.ez_sitemap')
        );

        return $response;
    }
}
