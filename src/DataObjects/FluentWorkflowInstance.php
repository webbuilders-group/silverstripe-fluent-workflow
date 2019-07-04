<?php

namespace WebbuildersGroup\FluentWorkflow\DataObjects;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Core\Injector\Injector;
use TractorCow\Fluent\State\FluentState;
use Symbiote\AdvancedWorkflow\DataObjects\WorkflowInstance;
use Symbiote\AdvancedWorkflow\DataObjects\WorkflowDefinition;
use Symbiote\AdvancedWorkflow\DataObjects\WorkflowActionInstance;

class FluentWorkflowInstance extends WorkflowInstance
{

    private static $table_name = "FluentWorkflowInstance";

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
