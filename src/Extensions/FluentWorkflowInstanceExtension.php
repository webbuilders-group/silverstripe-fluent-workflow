<?php
namespace WebbuildersGroup\FluentWorkflow\Extensions;

use SilverStripe\ORM\DataExtension;
use TractorCow\Fluent\State\FluentState;

class FluentWorkflowInstanceExtension extends DataExtension
{
    private static $db = [
        "TargetLocale" => "Varchar",
    ];

    private static $summary_fields = [
        "TargetLocale" => "Locale",
    ];

    public function onAfterWrite()
    {
        if (!$this->owner->TargetLocale) {
            $this->owner->TargetLocale = FluentState::singleton()->getLocale();
            $this->owner->write();
        }
    }
}
