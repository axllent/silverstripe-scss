<?php

namespace Axllent\Scss\Extensions;

use Axllent\Scss\ScssCompiler;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Extension;
use SilverStripe\View\Requirements;

/**
 * Add any rendered editor.scss to TinyMCE
 */
class HTMLEditor extends Extension
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

        $files = new \FilesystemIterator(
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

        // Silverstripe 4 & 5
        Config::modify()->merge(
            'SilverStripe\Forms\HTMLEditor\TinyMCEConfig',
            'editor_css',
            $editor_css
        );

        // Silverstripe 6
        Config::modify()->merge(
            'SilverStripe\TinyMCE\TinyMCEConfig',
            'editor_css',
            $editor_css
        );
    }
}
