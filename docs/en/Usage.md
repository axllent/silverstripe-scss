# Usage

Silverstripe-scss is a plug-and-play module, meaning there is little you need to do.

Once you have [installed][Installation.md] the module, simply use `Requirements` as you normally would, except using the *.scss names of your files.

For instance if you have a `themes/site/css/stylesheet.scss` file you wish to add, in your PageController you would have
```php
<?php
use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\View\Requirements;

class PageController extends ContentController
{
    public function init()
    {
        parent::init();
        Requirements::css('themes/site/css/stylesheet.scss');
        // OR
        Requirements::themedCSS('css/stylesheet.scss');
        // OR
        Requirements::themedCSS('css/stylesheet');
        // OR
        Requirements::themedCSS('stylesheet.scss');
        // OR
        Requirements::themedCSS('stylesheet');
    }
}
```

The library supports `themedCSS()` file resolving mechanism. The following 3 lines are equivalent:
```php
Requirements::css('themes/site/css/stylesheet.scss');
Requirements::themedCSS('css/stylesheet.scss');
Requirements::themedCSS('stylesheet');
```

This will parse the SCSS file (if needed), and write the resulting CSS file to `assets/_css/themes-site-css-stylesheet.css`
and automatically link the CSS in the templates to that file.

This also works if you are combining files:

```php
<?php
use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\View\Requirements;

class PageController extends ContentController
{
    public function init()
    {
        parent::init();
        Requirements::combine_files(
            'combined.css',
            [
                'themes/site/css/stylesheet.scss',
                'themes/site/css/colours.scss'
            ]
        );
        Requirements::process_combined_files();
    }
}
```


You can also include SCSS stylesheets from within your templates:

```html
<% require css(themes/site/css/stylesheet.scss) %>
<!-- OR -->
<% require themedCSS(css/stylesheet.scss) %>
<!-- OR -->
<% require themedCSS(stylesheet.scss) %>
<!-- OR -->
<% require themedCSS(stylesheet) %>
```

## Using custom variables and `$ThemeDir`

Please refer to [Configuration](Configuration.md) documentation.

## Source maps

Please refer to [Configuration](Configuration.md) documentation.
