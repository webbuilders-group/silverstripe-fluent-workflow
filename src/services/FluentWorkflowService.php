<?php

namespace WebbuildersGroup\FluentWorkflow\Services;

use SilverStripe\ORM\DataObject;
use Symbiote\AdvancedWorkflow\Services\WorkflowService;
use Symbiote\AdvancedWorkflow\Services\ExistingWorkflowException;
use WebbuildersGroup\FluentWorkflow\DataObjects\FluentWorkflowInstance;

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
}
