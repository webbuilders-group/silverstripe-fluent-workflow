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

/**
 * A WorkflowInstance is created whenever a user 'starts' a workflow.
 *
 * This 'start' is triggered automatically when the user clicks the relevant
 * button (eg 'apply for approval'). This creates a standalone object
 * that maintains the state of the workflow process.
 *
 * @method WorkflowDefinition Definition()
 * @method WorkflowActionInstance CurrentAction()
 * @method Member Initiator()
 *
 * @author  marcus@symbiote.com.au
 * @license BSD License (http://silverstripe.org/bsd-license/)
 * @package advancedworkflow
 */
class FluentWorkflowInstance extends WorkflowInstance
{

    private static $table_name = "FluentWorkflowInstance";

    /**
     * Get the target-object that this WorkflowInstance "points" to.
     *
     * Workflows are not restricted to being active on SiteTree objects,
     * so we need to account for being attached to anything.
     *
     * Sets Versioned::set_reading_mode() to allow fetching of Draft _and_ Published
     * content.
     *
     * @param boolean $getLive
     * @return null|DataObject
     */
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
