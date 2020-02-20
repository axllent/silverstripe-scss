<?php
namespace Axllent\Scss\Extensions;

use Axllent\Scss\ScssCompiler;
use FilesystemIterator;
use SilverStripe\Admin\LeftAndMainExtension;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\View\Requirements;

/**
 * Add any rendered editor.scss to TinyMCE
 */
class HTMLEditor extends LeftAndMainExtension
{
    /**
     * OnBeforeInit
     *
     * @return void
     */
    public function onBeforeInit()
    {
        $asset_handler = Requirements::backend()->getAssetHandler();

        $combined_folder = ScssCompiler::getProcessedCSSFolder();

        $folder = $asset_handler->getContentURL($combined_folder);

        if (!$folder) {
            return;
        }

        $files = new FilesystemIterator(
            Director::getAbsFile(Director::makeRelative($folder))
        );

        $editor_css = [];

        foreach ($files as $file) {
            $css = $file->getFilename();
            if (preg_match('/\-editor\.css$/', $css)) {
                $editor_css[] = Director::makeRelative($folder . '/' . $css);
            }
        }

        if (!count($editor_css)) {
            return; // no *-editor.css found
        }

        Config::modify()->merge(
            'SilverStripe\\Forms\\HTMLEditor\\TinyMCEConfig',
            'editor_css',
            $editor_css
        );
    }
}
