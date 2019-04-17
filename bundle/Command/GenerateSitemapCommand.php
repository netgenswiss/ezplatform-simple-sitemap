<?php

declare(strict_types=1);

namespace Prime\Bundle\EzSiteMapBundle\Command;

use eZ\Publish\API\Repository\UrlAliasService;
use eZ\Publish\API\Repository\Values\Content\Search\SearchResult;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\Core\QueryType\QueryTypeRegistry;
use Netgen\EzPlatformSiteApi\API\Values\Location;
use Netgen\EzPlatformSiteApi\API\Site;
use Prime\EzSiteMap\Factory\SitemapFactory;
use Prime\EzSiteMap\Sitemap\Configuration;
use Prime\EzSiteMap\Sitemap\Sitemap;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;


final class GenerateSitemapCommand extends Command
{
    /**
     * If we have multiple sitemaps, generate sitemap names using the following pattern.
     *
     * @var string
     */
    public const SITEMAP_NAME_PATTERN = 'sitemap_#INDEX#.xml';

    /**
     * @var ConfigResolverInterface
     */
    protected $configResolver;

    /**
     * @var \Netgen\EzPlatformSiteApi\API\FilterService
     */
    protected $filterService;

    /**
     * @var \eZ\Publish\API\Repository\UrlAliasService
     */
    protected $urlAliasService;

    /**
     * @var string
     */
    protected $webDir;

    /**
     * @var \Prime\EzSiteMap\Sitemap\Configuration
     */
    protected $sitemapConfiguration;

    /**
     * @var \eZ\Publish\Core\QueryType\QueryTypeRegistry
     */
    protected $queryTypeRegistry;

    /**
     * GenerateSitemapCommand constructor.
     *
     * @param \eZ\Publish\Core\QueryType\QueryTypeRegistry $queryTypeRegistry
     * @param \Netgen\EzPlatformSiteApi\API\Site $site
     * @param \eZ\Publish\API\Repository\UrlAliasService $urlAliasService
     * @param \Prime\EzSiteMap\Sitemap\Configuration $sitemapConfiguration
     * @param string $webDir
     */
    public function __construct(
        QueryTypeRegistry $queryTypeRegistry,
        Site $site,
        UrlAliasService $urlAliasService,
        Configuration $sitemapFactory,
        string $webDir
    ) {
        $this->queryTypeRegistry = $queryTypeRegistry;
        $this->site = $site;
        $this->urlAliasService = $urlAliasService;
        $this->sitemapConfiguration = $sitemapConfiguration;
        $this->webDir = $webDir;

        parent::__construct();
    }

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->setName('prime:sitemap:generate');
        $this->setDescription('Sitemap generation command.');
    }

    /**
     * Executes the current command.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input An InputInterface instance
     * @param \Symfony\Component\Console\Output\OutputInterface $output An OutputInterface instance
     *
     * @throws \RuntimeException When an error occurs
     *
     * @return int|null null or 0 if everything went fine, or an error code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->generateGoogleSitemap($input, $output);

        return 0;
    }

    private function generateGoogleSitemap(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');
        $output->writeln('Generating google sitemap(s)...');
        $output->writeln('');

        $totalCount = $this->getTotalCount();

        $output->writeln('');
        $output->writeln("Total locations to be indexed: {$totalCount}...");
        $output->writeln('');

        $sitemapFileCount = (int) ceil((float) $totalCount / $this->sitemapConfiguration->getMaxItemsPerPage());

        $sitemapFiles = [];

        $this->checkPath();

        for ($i = 1; $i <= $sitemapFileCount; ++$i) {
            $sitemapName = preg_replace('/#INDEX#/i', $i, self::SITEMAP_NAME_PATTERN);
            $sitemapWebPath = $this->sitemapConfiguration->getSitemapsIndexPath() . '/' . $sitemapName;
            $sitemapFileSystemPath = $this->webDir . '/' . $sitemapWebPath;

            $output->writeln('');
            $output->writeln("Generating sitemap {$sitemapFileSystemPath}...");
            $output->writeln('');

            $sitemap = new Sitemap();
            $results = $this->findLocations($this->maxItemsPerSitemap * ($i - 1), $this->maxItemsPerSitemap);
            $progress = new ProgressBar($output);
            $progress->start(count($results->searchHits));

            foreach ($results->searchHits as $searchHit) {
                $this->addItemToSitemap($sitemap, $searchHit->valueObject);
                $progress->advance();
            }

            $progress->finish();

            $generatedSitemapXML = $sitemap->export();
            // update lastMod value in sitemap index only if the sitemap file was created or modified
            if (!file_exists($sitemapFileSystemPath) || md5($generatedSitemapXML) !== md5_file($sitemapFileSystemPath)) {
                file_put_contents($sitemapFileSystemPath, $generatedSitemapXML);
            }

            $sitemapFiles[] = $sitemapName;

            unset($sitemap);
        }

        $existingSitemapFiles = array_diff(scandir($this->getPath(), SCANDIR_SORT_ASCENDING), ['..', '.']);

        foreach ($existingSitemapFiles as $existingSitemapFile) {
            if (!in_array($existingSitemapFile, $sitemapFiles, true)) {
                $sitemapFileSystemPath = $this->getFilePath($existingSitemapFile);
                unlink($sitemapFileSystemPath);
            }
        }

        $output->writeln('');
        $output->writeln('Sitemap(s) generated. Sitemaps index available on the /sitemap.xml route');
        $output->writeln('');
    }

    private function findLocations(int $offset = 0, int $limit = 500): SearchResult
    {
        $queryType = $this->queryTypeRegistry->getQueryType('SitemapLocations');
        $query = $queryType->getQuery(
            [
                'contentTypeList' => $this->sitemapConfiguration->getContentTypeList(),
                'rootLocation' => $this->getRootLocation(),
                'limit' => $limit,
                'offset' => $offset,
            ]
        );

        return $this->site
            ->getFilterService()
            ->filterLocations($query);
    }

    private function getTotalCount(): int
    {
        $queryType = $this->queryTypeRegistry->getQueryType('SitemapLocations');
        $query = $queryType->getQuery(
            [
                'contentTypeList' => $this->sitemapConfiguration->getContentTypeList(),
                'rootLocation' => $this->getRootLocation(),
                'limit' => 0,
                'offset' => 0,
            ]
        );

        return $this->site
            ->getFilterService()
            ->filterLocations($query)
            ->totalCount;
    }

    private function addItemToSitemap(Sitemap $sitemap, Location $location)
    {
        try {

            $locationPath = $this->urlAliasService
                ->reverseLookup(
                    $location->innerLocation,
                    $location->contentInfo->mainLanguageCode,
                    true
                )->path;

        } catch (\eZ\Publish\API\Repository\Exceptions\NotFoundException $e) {
            return;
        }

        $mainUrl = $this->sitemapConfiguration->getProtocol() . '://' . $this->sitemapConfiguration->getDomain() . $locationPath;
        $priority = 1 - (($location->depth - 1) * 0.1);

        $sitemap->addEntry($mainUrl, $location->contentInfo->modificationDate, $priority);
    }

    protected function getRootLocation(): Location
    {
        return $this->site
            ->getLoadService()
            ->loadLocation(
                $this->site->getSettings()->rootLocationId
            );
    }

    protected function getPath()
    {
        return $this->webDir . '/' . $this->sitemapConfiguration->getSitemapsIndexPath();
    }

    protected function getFilePath($file)
    {
        return $this->getPath() . '/'. $file;
    }

    protected function checkPath()
    {
        $filesystem = new Filesystem();

        if (!$filesystem->exists($this->getPath())) {
            $filesystem->mkdir($this->getPath(), 0775);
        }
    }
}
