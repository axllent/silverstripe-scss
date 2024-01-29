<?php

namespace Axllent\Scss;

use ScssPhp\ScssPhp\Compiler;
use ScssPhp\ScssPhp\OutputStyle;
use SilverStripe\Assets\FileNameFilter;
use SilverStripe\Assets\Storage\GeneratedAssetHandler;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Flushable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Path;

/**
 * ScssPHP CSS compiler for Silverstripe
 * ======================================
 *
 * Extension to add scssphp/scssphp CSS compiler to silverstripe
 *
 * Usage: See README.md
 *
 * License: MIT-style license http://opensource.org/licenses/MIT
 * Authors: Techno Joy (https://www.technojoy.co.nz)
 */
class ScssCompiler implements Flushable
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
    private static $sourcemap = 'inline';

    /**
     * Folder name for processed css under `assets`
     *
     * @var string
     */
    private static $processed_folder = '_css';

    /**
     * Array of processed files
     *
     * @var array
     */
    private static $processed_files = [];

    /**
     * Has a flush happened
     *
     * @var mixed
     */
    private static $already_flushed = false;

    /**
     * Other various cached values
     */
    private $config;
    private $asset_handler;
    private $file_name_filter;
    private $is_dev;

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
     * Process the scss file
     *
     * @param string $file
     */
    public function process($file): string
    {
        if (!preg_match('/\.scss$/', $file)) { // Not a scss file
            return $file;
        }
        if (!empty(self::$processed_files[$file])) { // already processed
            return self::$processed_files[$file];
        }

        $scss_file = $file;

        // return if not a file
        if (!is_file(Director::getAbsFile($scss_file))) {
            self::$processed_files[$file] = $file;

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
                $scss->setOutputStyle(OutputStyle::EXPANDED);
            } else {
                $scss->setOutputStyle(OutputStyle::COMPRESSED);
            }

            $scss->addImportPath(dirname(Director::getAbsFile($scss_file)) . '/');

            $variables = $this->config->get(__CLASS__, 'variables');

            $variables['BaseURL'] = '"' . $base_url . '"';

            $variables['BaseFolder'] = '"' . $base_folder . '"';

            $theme_dir = rtrim(
                $this->config->get(__CLASS__, 'theme_dir'),
                '/'
            ) . '/';

            if ($theme_dir) {
                $variables['ThemeDir'] = '"' . $base_url
                . rtrim(ltrim($theme_dir, '/'), '/') . '"';
            }

            $scss->addVariables($variables);

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

                if ('inline' == strtolower($sourcemap)) {
                    $scss->setSourceMap(Compiler::SOURCE_MAP_INLINE);
                    $scss->setSourceMapOptions($map_options);
                } elseif ('file' == strtolower($sourcemap)) {
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

        self::$processed_files[$file]        = $parsed_file;
        self::$processed_files[$parsed_file] = $parsed_file;

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

        if (!self::$already_flushed && '' != $css_dir) {
            // remove public/assets/_css folder
            $gah = Injector::inst()->get(GeneratedAssetHandler::class);
            $gah->removeContent($css_dir);
            // make sure we only flush once per request, not for each *.scss
            self::$already_flushed = true;
        }
    }

    /**
     * Return the processed CSS folder name
     */
    public static function getProcessedCSSFolder(): string
    {
        return Config::inst()->get(
            __CLASS__,
            'processed_folder'
        );
    }
}
