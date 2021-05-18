<?php
namespace Axllent\Scss;

use InvalidArgumentException;
use ScssPhp\ScssPhp\Compiler;
use SilverStripe\Assets\FileNameFilter;
use SilverStripe\Assets\Storage\GeneratedAssetHandler;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Flushable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Manifest\ModuleResourceLoader;
use SilverStripe\Core\Path;
use SilverStripe\View\Requirements_Backend;
use SilverStripe\View\SSViewer;
use SilverStripe\View\ThemeResourceLoader;

/**
 * Scssphp CSS compiler for silverstripe
 * ======================================
 *
 * Extension to add scssphp/scssphp CSS compiler to silverstripe
 *
 * Usage: See README.md
 *
 * License: MIT-style license http://opensource.org/licenses/MIT
 * Authors: Techno Joy (https://www.technojoy.co.nz)
 */
class ScssCompiler extends Requirements_Backend implements Flushable
{
    /**
     * Custom variables
     *
     * @config
     */
    private static $variables = [];

    /**
     * Relative theme directory
     *
     * @var mixed
     */
    private static $theme_dir = false;

    /**
     * Sourcemap type
     *
     * @var string file|inline|false
     */
    private static $sourcemap = 'file';

    /**
     * Folder name for processed css under `assets`
     *
     * @var string
     */
    private static $processed_folder = '_css';

    /**
     * Array of aprocessed files
     *
     * @var array
     */
    private static $_processed_files = [];

    /**
     * Has a flush happened
     *
     * @var mixed
     */
    private static $_already_flushed = false;

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->config = Config::inst();

        $this->asset_handler = $this->getAssetHandler();

        $this->file_name_filter = FileNameFilter::create();

        $this->is_dev = Director::isDev();
    }

    /**
     * Gets the default backend storage for generated files
     *
     * @return GeneratedAssetHandler
     */
    public function getAssetHandler()
    {
        return Injector::inst()->get(GeneratedAssetHandler::class);
    }

    /**
     * Register the given stylesheet into the list of requirements.
     * Processes *.scss files if detected and rewrites URLs
     *
     * @param string $file    The CSS file to load, relative to site root
     * @param string $media   Media types (e.g. 'screen,projector')
     * @param array  $options List of options.
     *
     * @return void
     */
    public function css($file, $media = null, $options = [])
    {
        $file     = ModuleResourceLoader::singleton()->resolvePath($file);
        $css_file = $this->processScssFile($file);

        return parent::css($css_file, $media, $options);
    }

    /**
     * Resolve themed SCSS path
     *
     * @param string $name   Name of SCSS file without extension
     * @param array  $themes List of themes, Defaults to SSViewer::get_themes()
     *
     * @return string Path to resolved SCSS file (relative to base dir)
     */
    public static function findThemedSCSS($name, $themes = null)
    {
        if ($themes === null) {
            $themes = SSViewer::get_themes();
        }

        if (substr($name, -5) !== '.scss') {
            $name .= '.scss';
        }
        $filename = ThemeResourceLoader::inst()
            ->findThemedResource("scss/$name", $themes);

        if ($filename === null) {
            $filename = ThemeResourceLoader::inst()
                ->findThemedResource($name, $themes);
        }

        return $filename;
    }

    /**
     * Process any scss files and return new filenames
     *
     * @param string $combinedFileName Filename of combined file relative to docroot
     * @param array  $files            Array of filenames relative to docroot
     * @param array  $options          Array of options for combining files.
     *
     * @return void
     *
     * @See Requirements_Backend->combineFiles() for options
     */
    public function combineFiles($combinedFileName, $files, $options = [])
    {
        $new_files = [];

        foreach ($files as $file) {
            $file        = ModuleResourceLoader::singleton()->resolvePath($file);
            $new_files[] = $this->processScssFile($file);
        }

        return parent::combineFiles($combinedFileName, $new_files, $options);
    }

    /**
     * Registers the given themeable stylesheet as required.
     *
     * A CSS/SCSS file in the current theme path name 'theme/css/$name.css' is
     * first searched for, and it that doesn't exist and the module parameter is
     * set then a CSS file with that name in the module is used.
     *
     * @param string $name  The name of the file - eg '/css/File.css' would
     *                      have the name 'File'
     * @param string $media Comma-separated list of media types to use in the
     *                      link tag (e.g. 'screen,projector')
     *
     * @return void
     */
    public function themedCSS($name, $media = null)
    {
        $path = self::findThemedSCSS($name, SSViewer::get_themes());
        if ($path) {
            $this->css($path, $media);
        } else {
            $path = ThemeResourceLoader::inst()
                ->findThemedCSS($name, SSViewer::get_themes());

            if ($path) {
                $this->css($path, $media);
            } else {
                throw new InvalidArgumentException(
                    "The scss file doesn't exist. Please check if the file " .
                    "$name.scss exists in any context or search for "
                    . 'themedCSS references calling this file in your templates.'
                );
            }
        }
    }

    /**
     * Process scss file (if detected) and return new URL
     *
     * @param string $file CSS filename
     *
     * @return string CSS filename
     */
    public function processScssFile($file)
    {
        if (!preg_match('/\.scss$/', $file)) { // Not a scss file
            return $file;
        } elseif (!empty(self::$_processed_files[$file])) { // already processed
            return self::$_processed_files[$file];
        }

        $scss_file = $file;

        // return if not a file
        if (!is_file(Director::getAbsFile($scss_file))) {
            self::$_processed_files[$file] = $file;

            return $file;
        }

        // Generate CSS filename including original path to avoid conflicts.
        // eg: themes/site/css/file.scss becomes themes-site-css-file.css
        $url_friendly_css_name = $this->file_name_filter->filter(
            str_replace('/', '-', preg_replace('/\.scss$/i', '', $scss_file))
        ) . '.css';

        $css_file = self::getProcessedCSSFolder() . '/' . $url_friendly_css_name;

        $output_file = $this->asset_handler->getContentURL($css_file);

        // absolute path to asset
        $real_src  = Director::getAbsFile($scss_file);
        $real_path = Path::join(PUBLIC_PATH, Director::makeRelative($output_file));

        if (is_null($output_file) || $this->is_dev
            && (filemtime($real_path) < filemtime($real_src) || isset($_GET['flushstyles']))
        ) {
            $base_url = Director::baseURL();

            $base_folder = Director::baseFolder();

            $scss_base = dirname($base_url . $scss_file) . '/';

            $scss = new Compiler();

            if ($this->is_dev) {
                $scss->setFormatter('ScssPhp\ScssPhp\Formatter\Expanded');
            } else {
                $scss->setFormatter('ScssPhp\ScssPhp\Formatter\Crunched');
            }

            $scss->addImportPath(dirname(Director::getAbsFile($scss_file)) . '/');

            $variables = $this->config->get(__CLASS__, 'variables');

            $variables['BaseURL'] = '"' . $base_url . '"';

            $variables['BaseFolder'] = '"' . $base_folder . '"';

            $theme_dir = rtrim(
                $this->config->get(__CLASS__, 'theme_dir'), '/'
            ) . '/';

            if ($theme_dir) {
                $variables['ThemeDir'] = '"' . $base_url
                . rtrim(ltrim($theme_dir, '/'), '/') . '"';
            }

            $scss->setVariables($variables);

            $map_options = [];

            $sourcemap = $this->config->get(__CLASS__, 'sourcemap');
            if ($sourcemap
                && $this->is_dev
                && in_array(strtolower($sourcemap), ['inline', 'file'])
            ) {
                $map_options = [
                    'sourceMapRootpath' => $scss_base,
                    'sourceMapBasepath' => dirname(Director::getAbsFile($scss_file)),
                ];

                if (strtolower($sourcemap) == 'inline') {
                    $scss->setSourceMap(Compiler::SOURCE_MAP_INLINE);
                    $scss->setSourceMapOptions($map_options);
                } elseif (strtolower($sourcemap) == 'file') {
                    $map_options['sourceMapWriteTo'] = $css_file . '.map';
                    $map_options['sourceMapURL']     = basename($css_file . '.map');
                    $scss->setSourceMapOptions($map_options);
                    $scss->setSourceMap(Compiler::SOURCE_MAP_FILE);
                }
            }

            $scss_filename = basename($scss_file);

            $result = $scss->compileString(
                file_get_contents(Director::getAbsFile($scss_file)),
                $scss_filename
            );

            if (!empty($map_options['sourceMapWriteTo'])) {
                $this->asset_handler->setContent(
                    $map_options['sourceMapWriteTo'],
                    $result->getSourceMap()
                );
            }

            $this->asset_handler->setContent($css_file, $result->getCss());
            $output_file = $this->asset_handler->getContentURL($css_file);
        }

        $parsed_file = Director::makeRelative($output_file);

        self::$_processed_files[$file]        = $parsed_file;
        self::$_processed_files[$parsed_file] = $parsed_file;

        return $parsed_file;
    }

    /**
     * Triggered early in the request when a flush is requested
     * Deletes the scss build folder
     *
     * @return void
     */
    public static function flush()
    {
        $css_dir = self::getProcessedCSSFolder();

        if (!self::$_already_flushed && $css_dir != '') {
            // remove public/assets/_css folder
            $gah = Injector::inst()->get(GeneratedAssetHandler::class);
            $gah->removeContent($css_dir);
            // make sure we only flush once per request, not for each *.scss
            self::$_already_flushed = true;
        }
    }

    /**
     * Return the processed CSS folder name
     *
     * @return string
     */
    public static function getProcessedCSSFolder()
    {
        return Config::inst()->get(
            __CLASS__,
            'processed_folder'
        );
    }
}
