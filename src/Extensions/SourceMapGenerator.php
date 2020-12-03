<?php
namespace Axllent\Scss\Extensions;

use ScssPhp\ScssPhp\SourceMap\SourceMapGenerator as ScssPhp_SourceMapGenerator;
use SilverStripe\View\Requirements;

class SourceMapGenerator extends ScssPhp_SourceMapGenerator
{
    /**
     * Options
     *
     * @var array
     */
    private $_options;

    /**
     * Class Contructor
     *
     * @param array $options Options
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);
        // can't access options from the child class, so we'll do it the hard way
        $this->_options = array_merge($this->defaultOptions, $options);
    }

    /**
     * Saves the source map to a file
     *
     * @param string $content The content to write
     *
     * @return string URL to saved file
     */
    public function saveMap($content)
    {
        $asset_handler = Requirements::backend()->getAssetHandler();

        $css_file = $this->_options['sourceMapWriteTo'];
        $asset_handler->setContent($css_file, $content);
        $url = $asset_handler->getContentURL($css_file);

        $this->_options['sourceMapURL'] = $url;

        return $this->_options['sourceMapURL'];
    }
}
