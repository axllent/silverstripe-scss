<?php
namespace Axllent\Scss\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\View\Requirements;

class ErrorPageControllerExtension extends Extension
{
    /**
     * Do not combine requirements on ErrorPages
     * Combined files chage names on rebuild and clash with static error pages
     *
     * @return void
     */
    public function onBeforeInit()
    {
        Requirements::set_combined_files_enabled(false);
    }
}
