<?php

declare(strict_types=1);

namespace Prime\EzSiteMap\Query;

use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\Query\SortClause;
use eZ\Publish\Core\QueryType\OptionsResolverBasedQueryType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SitemapQueryType extends OptionsResolverBasedQueryType
{
    public static function getName()
    {
        return self::class;
    }

    protected function doGetQuery(array $parameters)
    {
        /** @var Location $rootLocation */
        $rootLocation = $parameters['rootLocation'];
        $contentTypes = $parameters['contentTypeList'];

        $query = new LocationQuery();

        $criteria = [
            new Criterion\Subtree($rootLocation->pathString),
            new Criterion\Visibility(Criterion\Visibility::VISIBLE),
            new Criterion\Location\IsMainLocation(Criterion\Location\IsMainLocation::MAIN),
            new Criterion\ContentTypeIdentifier($contentTypes),
        ];

        if (isset($parameters['excludedNodes']) && !empty($parameters['excludedNodes'])) {

            foreach ($parameters['excludedNodes'] as $node) {
                $criteria[] = new Criterion\LogicalNot(new Criterion\Subtree($node->pathString));
            }
        }

        $query->filter = new Criterion\LogicalAnd($criteria);

        $query->sortClauses = [
            new SortClause\Location\Depth(LocationQuery::SORT_ASC),
            new SortClause\DatePublished(LocationQuery::SORT_DESC),
        ];

        if (isset($parameters['offset'])) {
            $query->offset = $parameters['offset'];
        }

        if (isset($parameters['limit'])) {
            $query->limit = $parameters['limit'];
        }

        return $query;
    }

    protected function configureOptions(OptionsResolver $optionsResolver)
    {
        $optionsResolver->setDefined(['offset', 'limit', 'contentTypeList', 'rootLocation', 'excludedNodes']);
        $optionsResolver->setAllowedTypes('offset', 'int');
        $optionsResolver->setAllowedTypes('limit', 'int');
        $optionsResolver->setAllowedTypes('contentTypeList', 'array');
        $optionsResolver->setAllowedTypes('rootLocation', Location::class);
        $optionsResolver->setAllowedTypes('excludedNodes', 'array');
        $optionsResolver->setRequired(['contentTypeList', 'rootLocation', 'offset', 'limit']);
    }
}
