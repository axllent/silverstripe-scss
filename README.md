# ScssPHP compiler for Silverstripe

A wrapper for [scssphp](https://scssphp.github.io/scssphp/) to integrate [SCSS](http://sass-lang.com/) compiling directly into Silverstripe.

SCSS files are only compiled when needed, or when a `?flush` is done.

## Features

- Integrates [scssphp](https://scssphp.github.io/scssphp/) seamlessly into Silverstripe
- Includes flushing option (`?flushstyles`) to regenerate CSS stylesheets (ie. force undetected SCSS changes with @import). Note: this only applies to sites in `dev` mode. Alternatively use `?flush` to flush everything including stylesheets.
- Writes processed `*.scss` files into `assets/_css/` and automatically modifies `Requirements` paths
- Allows custom global variables to be passed through to SCSS compiling (yaml configuration)
- Basic support for `$ThemeDir` (eg: `url('#{$ThemeDir}/images/logo.png')` (see [Configuration](docs/en/Configuration.md))
- Automatic compression of CSS files when in `live` mode (may require an initial `?flush`)
- Adds any processed `editor.scss` files to TinyMCE (must be included in your front-end template)
- Source maps (either inline or file) in `dev` mode only, can be disabled

## Requirements

- Silverstripe ^5

## Installation

```
composer require axllent/silverstripe-scss
```

## Usage

You need refer to your SCSS files by their full SCSS file names (eg:`stylesheet.scss`).

## Example

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
        // OR search for the scss in your $themeDirs
        Requirements::themedCSS('css/stylesheet.scss');
    }
}
```

The generated HTML will point automatically to the **processed** CSS file in `assets/_css/`
rather than the original SCSS file location, for example

```
<link rel="stylesheet" type="text/css"  href="/assets/_css/themes-site-css-stylesheet.css?m=123456789" />
```

## Further documentation

- [Usage.md](docs/en/Usage.md) for usage examples.
- [Configuration.md](docs/en/Configuration.md) for configuration options.
- View [Changelog](CHANGELOG.md)
