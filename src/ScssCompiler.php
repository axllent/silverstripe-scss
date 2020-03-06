<?php
namespace Axllent\Scss;

use Axllent\Scss\Extensions\SourceMapGenerator;
use ScssPhp\ScssPhp\Compiler;
use SilverStripe\Assets\FileNameFilter;
use SilverStripe\Assets\Storage\GeneratedAssetHandler;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Flushable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Manifest\ModuleResourceLoader;
use SilverStripe\View\Requirements_Backend;

/**
 * Scssphp CSS compiler for SilverStripe
 * ======================================
 *
 * Extension to add scssphp/scssphp CSS compiler to SilverStripe
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
     * Process scss file (if detected) and return new URL
     *
     * @param String $file CSS filename
     *
     * @return String CSS filename
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

        // Generate a new CSS filename that includes the original path
        // to avoid naming conflicts.
        // eg: themes/site/css/file.scss becomes themes-site-css-file.css
        $url_friendly_css_name = $this->file_name_filter->filter(
            str_replace('/', '-', preg_replace('/\.scss$/i', '', $scss_file))
        ) . '.css';

        $css_file = self::getProcessedCSSFolder() . '/' . $url_friendly_css_name;

        $output_file = $this->asset_handler->getContentURL($css_file);

        if (is_null($output_file)
            || $this->is_dev
            && (filemtime(
                Director::makeRelative($output_file)
            ) < filemtime(
                Director::getAbsFile($scss_file)
            ) || isset($_GET['flushstyles']))
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

            $theme_dir = rtrim($this->config->get(__CLASS__, 'theme_dir'), '/') . '/';
            if ($theme_dir) {
                $variables['ThemeDir'] = '"' . $base_url . rtrim(ltrim($theme_dir, '/'), '/') . '"';
            }

            $scss->setVariables($variables);

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
                    $scss->setSourceMap(new SourceMapGenerator($map_options));
                }
            }

            $scss_filename = basename($scss_file);

            $raw_css = $scss->compile(
                file_get_contents(Director::getAbsFile($scss_file)),
                $scss_filename
            );

            $this->asset_handler->setContent($css_file, $raw_css);
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
            // remove /public/assets/_css
            $ah = Injector::inst()->get(GeneratedAssetHandler::class);
            $ah->removeContent($css_dir);
            // make sure we only flush once per request and not for each *.less
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
