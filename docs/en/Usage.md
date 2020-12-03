# Usage

SilverStripe-scss is a plug-and-play module, meaning there is little you need to do.

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
    }
}
```

The library supports `themedCSS()` file resolving mechanism. The following 3 lines are equivalent:
```php
Requirements::css('themes/site/scss/main.scss');
Requirements::themedCSS('scss/main.scss');
Requirements::themedCSS('main');
```

This will parse the scss file (if needed), and write the resulting CSS file to `assets/_css/themes-site-css-stylesheet.css`
and automatically link the CSS in the templates to that file.

Note that the lookups for SCSS files (when no folder provided in path) are done in `scss` folder (not `css`).

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
        Requirements::combine_files('combined.css', [
            'themes/site/css/stylesheet.scss',
            'themes/site/css/colours.scss'
        ]);
        Requirements::process_combined_files();
    }
}
```

For the sake of filename resolving to use in `Requirements::combine_files`, you can use `Requirements::findThemedSCSS()`:
```php
<?php
use SilverStripe\View\Requirements;
use Axllent\Scss\ScssCompiler;

// ...
Requirements::combine_files('combined.css', [
    ScssCompiler::findThemedSCSS('css/stylesheet'),
    ScssCompiler::findThemedSCSS('css/colours'),
]);
Requirements::process_combined_files();
```

You can also include scss stylesheets from within your templates:
```
<% require css(themes/site/scss/stylesheet.scss) %>
<!-- OR -->
<% require themedCSS(scss/stylesheet.scss) %>
<!-- OR -->
<% require themedCSS(stylesheet.scss) %>
<!-- OR -->
<% require themedCSS(stylesheet) %>
```

## Using custom variables and `$ThemeDir`

Please refer to [Configuration](Configuration.md) documentation.

## Source maps

Please refer to [Configuration](Configuration.md) documentation.
