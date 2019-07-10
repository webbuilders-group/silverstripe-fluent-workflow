<?php
namespace WebbuildersGroup\FluentWorkflow\Extensions;

use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\ListboxField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_Base;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\ORM\RelationList;
use SilverStripe\Security\Permission;
use Symbiote\AdvancedWorkflow\DataObjects\WorkflowDefinition;
use Symbiote\AdvancedWorkflow\Extensions\WorkflowApplicable;
use TractorCow\Fluent\Extension\FluentFilteredExtension;
use TractorCow\Fluent\Model\Locale;
use TractorCow\Fluent\State\FluentState;
use WebbuildersGroup\FluentWorkflow\DataLists\FluentWorkflowDefinitionList;
use WebbuildersGroup\FluentWorkflow\DataObjects\FluentWorkflowInstance;

class FluentWorkflowApplicable extends WorkflowApplicable
{
    private static $has_one = [
        'WorkflowDefinition' => WorkflowDefinition::class,
    ];

    private static $many_many = [
        "AdditionalWorkflowDefinitions" => WorkflowDefinition::class,
    ];
    
    private static $many_many_extraFields = [
        'AdditionalWorkflowDefinitions' => [
            'JunctionLocale' => 'Varchar(10)',
        ],
    ];
    
    private static $field_include = [
        'WorkflowDefinitionID',
    ];
    
    public function updateFields(FieldList $fields)
    {
        if (!$this->owner->ID) {
            return $fields;
        }

        $tab = $fields->fieldByName('Root') ? $fields->findOrMakeTab('Root.Workflow') : $fields;

        if (Permission::check('APPLY_WORKFLOW')) {
            $fields->removeByName('WorkflowDefinitionID');
            $definition = new DropdownField('WorkflowDefinitionID', _t('WorkflowApplicable.DEFINITION', 'Applied Workflow'));
            $definitions = $this->getWorkflowService()->getDefinitions()->map()->toArray();
            $definition->setSource($definitions);
            $definition->setEmptyString(_t('WorkflowApplicable.INHERIT', 'Inherit from parent'));
            $tab->push($definition);


            // Allow an optional selection of additional workflow definitions.

            if ($this->owner->WorkflowDefinitionID) {
                $fields->removeByName('AdditionalWorkflowDefinitions');
                unset($definitions[$this->owner->WorkflowDefinitionID]);
                $tab->push(ListboxField::create('AdditionalWorkflowDefinitions', _t('WorkflowApplicable.ADDITIONAL_WORKFLOW_DEFINITIONS', 'Additional Workflows'))->setSource($definitions));
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
                "TargetLocale" => FluentState::singleton()->getLocale(),
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
    
    /**
     * Switches out the relationship list for the workflow definitions with FluentWorkflowDefinitionList
     * @param ManyManyList $list
     */
    public function updateManyManyComponents(RelationList &$list)
    {
        //If we have a ManyManyList swap with a FluentManyManyList
        if ($list instanceof ManyManyList && $list->dataClass() == WorkflowDefinition::class) {
            $list = FluentWorkflowDefinitionList::create($list->dataClass(), $list->getJoinTable(), $list->getLocalKey(), $list->getForeignKey(), ($this->owner->existsInLocale($this->owner->Locale) ? $this->owner->Locale : $this->owner->getSourceLocale()->Locale), $list->getExtraFields())
                                        ->forForeignID($list->getForeignID())
                                        ->setDataQueryParam($list->dataQuery()->getQueryParams())
                                        ->setLocaleFilterEnabled(($this->owner->existsInLocale($this->owner->Locale) || ($this->owner->has_extension(FluentFilteredExtension::class) && $this->owner->isAvailableInLocale(Locale::getByLocale($this->owner->Locale)))));
        }
    }
    
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        
        if (!$this->owner->existsInLocale()) {
            $this->owner->WorkflowDefinitionID = 0;
        }
    }
}
