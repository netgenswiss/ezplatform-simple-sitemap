<?php

namespace Prime\Bundle\EzSiteMapBundle\Controller;

use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Netgen\Bundle\EzPlatformSiteApiBundle\Controller\Controller;
use Netgen\Bundle\OpenGraphBundle\MetaTag\CollectorInterface;
use Prime\EzSiteMapBundle\Sitemap\SitemapIndex;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;

class SitemapController extends Controller
{

    /**
     * @var ConfigResolverInterface
     */
    protected $configResolver;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var CollectorInterface
     */
    protected $tagCollector;

    /**
     * @var string
     */
    protected $domain;

    /**
     * @var string
     */
    protected $webDir;

    /**
     * @var string
     */
    protected $protocol = 'https';

    /**
     * SitemapController constructor.
     * @param $configResolver ConfigResolverInterface
     * @param $router RouterInterface
     * @param $tagCollector CollectorInterface
     * @param $domain string
     * @param $webDir string
     */
    public function __construct( ConfigResolverInterface $configResolver, RouterInterface $router, CollectorInterface $tagCollector, $domain, $webDir)
    {
        $this->configResolver = $configResolver;
        $this->router = $router;
        $this->tagCollector = $tagCollector;
        $this->domain = $domain;
        $this->webDir = $webDir;
    }

    public function getSitemap()
    {
        $sitemapsDir = $this->webDir . '/sitemaps';

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
                $loc = $this->protocol . "://" . $this->domain . '/sitemaps/' . $sitemapFile;
                $lastModified = filemtime($sitemapsDir . '/' . $sitemapFile);
                $sitemap->addSitemap($loc, $lastModified);
            }
        }

        $response = new Response($sitemap->export());
        $response->headers->set('Content-Type','text/xml');
        $response->setCharset('utf-8');
        $response->setPublic();
        $response->setVary('Accept-Encoding');
        $response->setSharedMaxAge( 3600 );

        return $response;
    }
}
