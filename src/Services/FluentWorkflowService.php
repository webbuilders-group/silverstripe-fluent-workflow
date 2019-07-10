<?php

namespace WebbuildersGroup\FluentWorkflow\Services;

use SilverStripe\Core\Convert;
use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\DataObject;
use TractorCow\Fluent\State\FluentState;
use Symbiote\AdvancedWorkflow\Services\WorkflowService;
use Symbiote\AdvancedWorkflow\DataObjects\WorkflowInstance;
use Symbiote\AdvancedWorkflow\Extensions\WorkflowApplicable;
use Symbiote\AdvancedWorkflow\DataObjects\WorkflowDefinition;
use Symbiote\AdvancedWorkflow\Extensions\FileWorkflowApplicable;
use Symbiote\AdvancedWorkflow\Services\ExistingWorkflowException;
use WebbuildersGroup\FluentWorkflow\DataObjects\FluentWorkflowInstance;
use WebbuildersGroup\FluentWorkflow\Extensions\FluentWorkflowApplicable;

class FluentWorkflowService extends WorkflowService
{
    public function startWorkflow(DataObject $object, $workflowID = null)
    {
        $existing = $this->getWorkflowFor($object);
        if ($existing) {
            throw new ExistingWorkflowException(_t(
                'WorkflowService.EXISTING_WORKFLOW_ERROR',
                "That object already has a workflow running"
            ));
        }

        $definition = null;
        if ($workflowID) {
            // Retrieve the workflow definition that has been triggered.

            $definition = $this->getDefinitionByID($object, $workflowID);
        }
        if (is_null($definition)) {
            // Fall back to the main workflow definition.

            $definition = $this->getDefinitionFor($object);
        }

        if ($definition) {
            $instance = FluentWorkflowInstance::create();
            $instance->beginWorkflow($definition, $object);
            $instance->execute();
        }
    }

    /**
     * Gets the workflow for the given item
     *
     * The item can be
     *
     * a data object in which case the ActiveWorkflow will be returned,
     * an action, in which case the Workflow will be returned
     * an integer, in which case the workflow with that ID will be returned
     *
     * @param mixed $item
     * @param bool $includeComplete
     * @return WorkflowInstance|null
     */
    public function getWorkflowFor($item, $includeComplete = false)
    {
        $locale = FluentState::singleton()->getLocale();

        $id = $item;

        if ($item instanceof WorkflowAction) {
            $id = $item->WorkflowID;
            return DataObject::get_by_id(WorkflowInstance::class, $id);
        } elseif (
            is_object($item) && ($item->hasExtension(FluentWorkflowApplicable::class)
                || $item->hasExtension(FileWorkflowApplicable::class))
        ) {
            $filter = sprintf(
                '"TargetClass" = \'%s\' AND "TargetID" = %d AND "TargetLocale" = \'%s\' ',
                Convert::raw2sql(ClassInfo::baseDataClass($item)),
                $item->ID,
                $locale
            );
            $complete = $includeComplete ? 'OR "WorkflowStatus" = \'Complete\' ' : '';
            $do = FluentWorkflowInstance::get()->where(
                $filter . ' AND ("WorkflowStatus" = \'Active\' OR "WorkflowStatus"=\'Paused\' ' . $complete . ')'
            )->first();

            return $do;
        }
    }

    /**
     * Gets the workflow definition for a given dataobject, if there is one
     *
     * Will recursively query parent elements until it finds one, if available
     *
     * @param DataObject $dataObject
     */
    public function getDefinitionFor(DataObject $dataObject)
    {
        if (
            $dataObject->hasExtension(WorkflowApplicable::class)
            || $dataObject->hasExtension(FileWorkflowApplicable::class)
        ) {
            $definitionID = $dataObject->WorkflowDefinitionID;
            if ($definitionID) {
                return DataObject::get_by_id(WorkflowDefinition::class, $definitionID);
            }
            if ($dataObject->hasMethod('useInheritedWorkflow') && !$dataObject->useInheritedWorkflow()) {
                return null;
            }
            if ($dataObject->ParentID) {
                return $this->getDefinitionFor($dataObject->Parent());
            }
            if ($dataObject->hasMethod('workflowParent')) {
                $obj = $dataObject->workflowParent();
                if ($obj) {
                    return $this->getDefinitionFor($obj);
                }
            }
        }
        return null;
    }
}
