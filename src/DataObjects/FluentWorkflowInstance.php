<?php
namespace WebbuildersGroup\FluentWorkflow\DataObjects;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;
use Symbiote\AdvancedWorkflow\DataObjects\WorkflowInstance;
use TractorCow\Fluent\State\FluentState;
use WebbuildersGroup\FluentWorkflow\Extensions\FluentWorkflowInstanceExtension;

class FluentWorkflowInstance extends WorkflowInstance
{

    private static $table_name = "FluentWorkflowInstance";

    private static $extensions = [
        FluentWorkflowInstanceExtension::class,
    ];

    public function getTarget($getLive = false)
    {
        return FluentState::singleton()->withState(function ($state) use ($getLive) {

            $state->setLocale($this->TargetLocale);

            if ($this->TargetID && $this->TargetClass) {
                $versionable = Injector::inst()->get($this->TargetClass)->has_extension(Versioned::class);
                $targetObject = null;

                if (!$versionable && $getLive) {
                    return;
                }
                if ($versionable) {
                    $targetObject = Versioned::get_by_stage(
                        $this->TargetClass,
                        $getLive ? Versioned::LIVE : Versioned::DRAFT
                    )->byID($this->TargetID);
                }
                if (!$targetObject) {
                    $targetObject = DataObject::get_by_id($this->TargetClass, $this->TargetID);
                }

                return $targetObject;
            }
        });
    }
}
