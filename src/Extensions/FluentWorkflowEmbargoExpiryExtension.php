<?php
namespace WebbuildersGroup\FluentWorkflow\Extensions;

use Symbiote\AdvancedWorkflow\Extensions\WorkflowEmbargoExpiryExtension;

class FluentWorkflowEmbargoExpiryExtension extends WorkflowEmbargoExpiryExtension
{
    private static $field_include = [
        'DesiredPublishDate',
        'DesiredUnPublishDate',
        'PublishOnDate',
        'UnPublishOnDate',
        'AllowEmbargoedEditing',
        'PublishJobID',
        'UnPublishJobID',
    ];
    
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        
        if (!$this->owner->existsInLocale()) {
            $this->owner->DesiredPublishDate = null;
            $this->owner->DesiredUnPublishDate = null;
            $this->owner->PublishOnDate = null;
            $this->owner->UnPublishOnDate = null;
            $this->owner->AllowEmbargoedEditing = null;
            $this->owner->PublishJobID = 0;
            $this->owner->UnPublishJobID = 0;
        }
    }
}
