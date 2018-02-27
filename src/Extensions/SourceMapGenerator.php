<?php
namespace Axllent\Scss\Extensions;

use FilesystemIterator;
use SilverStripe\Admin\LeftAndMainExtension;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\View\Requirements;
use Leafo\ScssPhp\SourceMap\SourceMapGenerator as Leafo_SourceMapGenerator;

class SourceMapGenerator extends Leafo_SourceMapGenerator
{
    /**
     * @var array
     */
    private $options;

    public function __construct(array $options = [])
    {
    	parent::__construct($options);
    	// can't access options from the child class, so we'll do it the hard way
        $this->options = array_merge($this->defaultOptions, $options);
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

        $css_file = $this->options['sourceMapWriteTo'];
        $asset_handler->setContent($css_file, $content);
        $url = $asset_handler->getContentURL($css_file);

        $this->options['sourceMapURL'] = $url;
        return $this->options['sourceMapURL'];
    }
}
