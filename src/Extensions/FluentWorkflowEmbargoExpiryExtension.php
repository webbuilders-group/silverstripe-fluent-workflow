<?php

namespace WebbuildersGroup\FluentWorkflow\Extensions;

use Symbiote\AdvancedWorkflow\Extensions\WorkflowEmbargoExpiryExtension;

class FluentWorkflowEmbargoExpiryExtension extends WorkflowEmbargoExpiryExtension
{
    private static $field_include = array(
        'DesiredPublishDate',
        'DesiredUnPublishDate',
        'PublishOnDate',
        'UnPublishOnDate',
        'AllowEmbargoedEditing',
    );
}
