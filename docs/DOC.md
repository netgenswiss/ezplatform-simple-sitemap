Documentation
=============

## Using the bundle

You can generate the sitemap using this command:

```
php bin/console prime:sitemap:generate
```

### Available parameters

```yaml
parameters:
    prime.ez_sitemap.default.sitemap.domain: ''
    
    # Protocol to be used in sitemap url
    prime.ez_sitemap.default.sitemap.protocol: 'https'

    # Index directory name
    prime.ez_sitemap.default.sitemap.sitemaps_index_path: 'sitemaps'
    prime.ez_sitemap.default.sitemap.shared_max_age: 3600
    
    # List of content types to be included in sitemap
    prime.ez_sitemap.default.sitemap.content_type_list:
        - ng_article
        - ng_feedback_form
        - ng_frontpage
        - ng_landing_page

    # Maximum number of items per sitemap file
    prime.ez_sitemap.default.sitemap.max_items_per_sitemap: 500
    
    # Node ids to be excluded with their child objects, accepts array of integers
    prime.ez_sitemap.default.sitemap.excluded_nodes: ~
```
