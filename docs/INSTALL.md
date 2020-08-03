Installation instructions
=========================

Installation steps
------------------

### Use Composer

Run the following from your website root folder to install Prime eZ Sitemap Bundle:

```bash
$ composer require primedigital/ez-sitemap
```

### Activate the bundle

Activate required bundles in `app/AppKernel.php` file by adding them to the `$bundles` array in `registerBundles` method:

```php
public function registerBundles()
{
    ...
    $bundles[] = new Prime\Bundle\EzSiteMapBundle\PrimeEzSiteMapBundle();

    return $bundles;
}
```

### Configuration

You can configure the bundle using the following parameters:

```yaml
parameters:
    prime.ez_sitemap.default.sitemap.domain: '%ngmore.default.site_domain%'
    prime.ez_sitemap.default.sitemap.content_type_list:
        - ng_article
        - ng_feedback_form
        - ng_frontpage
        - ng_landing_page
    prime.ez_sitemap.default.sitemap.max_items_per_sitemap: 500
```

### Include routing in main `routing.yml` file:

```yaml
prime_ez_sitemap:
    resource: '@PrimeEzSiteMapBundle/Resources/config/routing.yml'
```

### Clear the caches

Clear the eZ Publish caches with the following command:

```bash
$ php app/console cache:clear
```

Do not forget to enable access to sitemap file in production .htaccess:

```
RewriteRule ^sitemaps/sitemap_1.xml - [L]
```
