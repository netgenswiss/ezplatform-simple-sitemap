<?php

namespace Prime\Bundle\EzSiteMapBundle\Command;

use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Netgen\EzPlatformSiteApi\API\Values\Location;
use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\Values\Content\Query\SortClause;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use Netgen\EzPlatformSiteApi\API\FilterService;
use eZ\Publish\API\Repository\UrlAliasService;
use Prime\EzSiteMapBundle\Sitemap;

/**
 * Class GenerateSitemapCommand
 * 
 * Code mostly by Hrvoje Knežević
 * 
 * @package Prime\Bundle\EzSiteMapBundle\Command
 */
class GenerateSitemapCommand extends Command
{
    /**
     * @var ConfigResolverInterface
     */
    protected $configResolver;

    /**
     * @var FilterService
     */
    protected $filterService;

    /**
     * @var UrlAliasService
     */
    protected $urlAliasService;

    /**
     * @var string
     */
    protected $domain;

    /**
     * @var string[]
     */
    protected $contentTypeList;

    /**
     * @var int
     */
    protected $maxItemsPerSitemap;

    /**
     * @var string
     */
    protected $webDir;

    /**
     * @var string
     */
    protected $applicationProtocol = 'https';

    /**
     * Delimits the content fetch to a specific part of the content tree
     *
     * @var string
     */
    const SITEMAP_PATH_LIMIT = "/1/2/";

    /**
     * If we have multiple sitemaps, generate sitemap names using the following pattern
     *
     * @var string
     */
    const SITEMAP_NAME_PATTERN = "sitemap_#INDEX#.xml";

    /**
     * Container folder for multiple sitemap files when we have a sitemap index in sitemap.xml
     *
     * @var string
     */
    const SITEMAPS_INDEX_PATH = "sitemaps";

    /**
     * GenerateSitemapCommand constructor.
     *
     * @param \eZ\Publish\Core\MVC\ConfigResolverInterface $configResolver
     * @param \Netgen\EzPlatformSiteApi\API\FilterService $filterService
     * @param \eZ\Publish\API\Repository\UrlAliasService $urlAliasService
     * @param string $domain
     * @param array $contentTypeList
     * @param int $maxItemsPerSitemap
     * @param string $webDir
     */
    public function __construct(
        ConfigResolverInterface $configResolver,
        FilterService $filterService,
        UrlAliasService $urlAliasService,
        string $domain,
        array $contentTypeList,
        int $maxItemsPerSitemap,
        string $webDir
    )
    {
        $this->configResolver       = $configResolver;
        $this->filterService        = $filterService;
        $this->urlAliasService      = $urlAliasService;
        $this->domain               = $domain;
        $this->contentTypeList      = $contentTypeList;
        $this->webDir               = $webDir;
        $this->maxItemsPerSitemap   = $maxItemsPerSitemap;

        parent::__construct();
    }

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->setName('prime:generate:sitemap')
            ->setDescription('Generate sitemap');
    }

    /**
     * Executes the current command.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input An InputInterface instance
     * @param \Symfony\Component\Console\Output\OutputInterface $output An OutputInterface instance
     *
     * @throws \RuntimeException When an error occurs
     *
     * @return null|int null or 0 if everything went fine, or an error code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->generateGoogleSitemap($input, $output);
        return 0;
    }

    private function generateGoogleSitemap(InputInterface $input, OutputInterface $output){
        $output->writeln('');
        $output->writeln( 'Generating google sitemap(s)...' );
        $output->writeln('');

        $totalCount = $this->getTotalCount();

        $output->writeln('');
        $output->writeln( "Total locations to be indexed: {$totalCount}..." );
        $output->writeln('');

        $sitemapFileCount = (int)ceil((float)$totalCount/$this->maxItemsPerSitemap);

        $sitemapFiles = [];
        if (!file_exists($this->webDir . '/' . self::SITEMAPS_INDEX_PATH)) {
            mkdir($this->webDir . '/' . self::SITEMAPS_INDEX_PATH, 0775, true);
        }

        for($i = 1; $i <= $sitemapFileCount; $i++) {
            $sitemapName = preg_replace('/#INDEX#/i', $i, self::SITEMAP_NAME_PATTERN);
            $sitemapWebPath = self::SITEMAPS_INDEX_PATH . '/' . $sitemapName;
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

        $existingSitemapFiles = array_diff(scandir( $this->webDir . '/' . self::SITEMAPS_INDEX_PATH, SCANDIR_SORT_ASCENDING), ['..', '.']);

        foreach ($existingSitemapFiles as $existingSitemapFile){
            if(!in_array($existingSitemapFile, $sitemapFiles)){
                $sitemapFileSystemPath = $this->webDir . '/' . self::SITEMAPS_INDEX_PATH . '/' . $existingSitemapFile;
                unlink($sitemapFileSystemPath);
            }
        }

        $output->writeln('');
        $output->writeln('Sitemap(s) generated. Sitemaps index available on the /sitemap.xml route');
        $output->writeln('');
    }

    private function findLocations(int $offset = 0, int $limit = 50000) {
        $query = new LocationQuery();
        $query->filter = new Criterion\LogicalAnd(
            [
                new Criterion\Subtree( self::SITEMAP_PATH_LIMIT ),
                new Criterion\Visibility( Criterion\Visibility::VISIBLE ),
                new Criterion\Location\IsMainLocation( Criterion\Location\IsMainLocation::MAIN),
                new Criterion\ContentTypeIdentifier( $this->contentTypeList )
            ]
        );
        $query->sortClauses = [ new SortClause\Location\Depth(LocationQuery::SORT_ASC), new SortClause\DatePublished( LocationQuery::SORT_DESC ) ];
        $query->offset = $offset;
        $query->limit = $limit;

        return $this->filterService->filterLocations( $query );
    }

    private function getTotalCount() {
        $query = new LocationQuery();
        $query->filter = new Criterion\LogicalAnd(
            [
                new Criterion\Subtree( self::SITEMAP_PATH_LIMIT ),
                new Criterion\Visibility( Criterion\Visibility::VISIBLE ),
                new Criterion\Location\IsMainLocation( Criterion\Location\IsMainLocation::MAIN),
                new Criterion\ContentTypeIdentifier( $this->contentTypeList )
            ]
        );
        $query->sortClauses = [ new SortClause\Location\Depth(LocationQuery::SORT_ASC), new SortClause\DatePublished( LocationQuery::SORT_DESC ) ];
        $query->offset = 0;
        $query->limit = 1;

        return $this->filterService->filterLocations( $query )->totalCount;
    }

    private function addItemToSitemap(Sitemap $sitemap, Location $location){
        $modified = $location->contentInfo->modificationDate->format( "c" );
        $mainLanguageCode = $location->contentInfo->mainLanguageCode;
        try{
            $locationPath = $this->urlAliasService->reverseLookup( $location->innerLocation, $mainLanguageCode, true )->path;
        }
        catch( \Exception $e){
            return;
        }
        $mainUrl = $this->applicationProtocol . "://" . $this->domain . $locationPath;
        $priority = 1 - ( ($location->depth - 1) * 0.1);

        $sitemap->addEntry( $mainUrl, $modified, $priority );
    }
}
