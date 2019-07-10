<?php

namespace WebbuildersGroup\FluentWorkflow\Extensions;

use SilverStripe\Dev\Debug;
use SilverStripe\Forms\FieldList;
use TractorCow\Fluent\Model\Locale;
use SilverStripe\Forms\ListboxField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Security\Permission;
use TractorCow\Fluent\State\FluentState;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Forms\GridField\GridFieldConfig_Base;
use Symbiote\AdvancedWorkflow\Extensions\WorkflowApplicable;
use Symbiote\AdvancedWorkflow\DataObjects\WorkflowDefinition;
use WebbuildersGroup\FluentWorkflow\DataObjects\FluentWorkflowInstance;

class FluentWorkflowApplicable extends WorkflowApplicable
{
    private static $has_one = [
        'WorkflowDefinition' => WorkflowDefinition::class,
        'FrWorkflowDefinition' => WorkflowDefinition::class,
    ];

    private static $many_many = [
        "AdditionalWorkflowDefinitions" => WorkflowDefinition::class,
        "FrAdditionalWorkflowDefinitions" => WorkflowDefinition::class,
    ];

    public function updateFields(FieldList $fields)
    {
        if (!$this->owner->ID) {
            return $fields;
        }

        $tab = $fields->fieldByName('Root') ? $fields->findOrMakeTab('Root.Workflow') : $fields;

        if (Permission::check('APPLY_WORKFLOW')) {
            $fields->removeByName('WorkflowDefinitionID');
            $fields->removeByName('FrWorkflowDefinitionID');
            $definition = new DropdownField(
                Locale::getCurrentLocale()->Locale === "en_US" ? 'WorkflowDefinitionID' : 'FrWorkflowDefinitionID',
                _t('WorkflowApplicable.DEFINITION', 'Applied Workflow')
            );
            $definitions = $this->getWorkflowService()->getDefinitions()->map()->toArray();
            $definition->setSource($definitions);
            $definition->setEmptyString(_t('WorkflowApplicable.INHERIT', 'Inherit from parent'));
            $tab->push($definition);


            // Allow an optional selection of additional workflow definitions.

            if ($this->owner->WorkflowDefinitionID) {
                $fields->removeByName('AdditionalWorkflowDefinitions');
                $fields->removeByName('FrAdditionalWorkflowDefinitions');
                unset($definitions[$this->owner->WorkflowDefinitionID]);
                $tab->push($additional = ListboxField::create(
                    Locale::getCurrentLocale()->Locale === "en_US" ? 'AdditionalWorkflowDefinitions' : 'FrAdditionalWorkflowDefinitions',
                    _t('WorkflowApplicable.ADDITIONAL_WORKFLOW_DEFINITIONS', 'Additional Workflows')
                ));
                $additional->setSource($definitions);
            }
        }

        // Display the effective workflow definition.

        if ($effective = $this->getWorkflowInstance()) {
            $title = $effective->Definition()->Title;
            $tab->push(ReadonlyField::create(
                'EffectiveWorkflow',
                _t('WorkflowApplicable.EFFECTIVE_WORKFLOW', 'Effective Workflow'),
                $title
            ));
        }

        if ($this->owner->ID) {
            $config = new GridFieldConfig_Base();
            $config->addComponent(new GridFieldEditButton());
            $config->addComponent(new GridFieldDetailForm());

            $insts = FluentWorkflowInstance::get()->filter([
                "TargetID" => $this->owner->ID,
                "TargetLocale" => Locale::getCurrentLocale()->Locale,
            ]);
            $log = new GridField(
                'WorkflowLog',
                _t('WorkflowApplicable.WORKFLOWLOG', 'Workflow Log'),
                $insts,
                $config
            );

            $tab->push($log);
        }
    }
    
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        
        if (!$this->owner->existsInLocale()) {
            if (Locale::getCurrentLocale()->Locale === "en_US") {
                $this->owner->WorkflowDefinitionID = 0;
            } else {
                $this->owner->FrWorkflowDefinition = 0;
            }
        }
    }
}
