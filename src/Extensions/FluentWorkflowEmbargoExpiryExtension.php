<?php
namespace WebbuildersGroup\FluentWorkflow\Extensions;

use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\FieldType\DBField;
use Symbiote\AdvancedWorkflow\Extensions\WorkflowEmbargoExpiryExtension;
use SilverStripe\ORM\FieldType\DBHTMLVarchar;

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
    
    /**
     * Updates the fields used in the cms
     * @param FieldList $fields Fields to be extended
     */
    public function updateCMSFields(FieldList $fields)
    {
        parent::updateCMSFields($fields);
        
        $translatedTooltipTitle = _t(__CLASS__ . ".FLUENT_ICON_TOOLTIP", 'Translatable field');
        $tooltip = DBField::create_field('HTMLFragment', "<span class='font-icon-translatable' title='$translatedTooltipTitle'></span>");
        
        
        //Update the publish date field to have the icon
        $field = $fields->dataFieldByName('DesiredPublishDate');
        if (!$field->hasClass('fluent__localised-field')) {
            $field
                ->setTitle(DBField::create_field('HTMLFragment', $tooltip . $field->Title()))
                ->addExtraClass('fluent__localised-field');
            
            $rightTitle = $field->RightTitle();
            if (!empty($rightTitle) && !(is_a($rightTitle, DBField::class))) {
                $field->setRightTitle(DBField::create_field(DBHTMLVarchar::class, $rightTitle));
            }
        }
        
        
        //Update the unpublish date field to have the icon
        $field = $fields->dataFieldByName('DesiredUnPublishDate');
        if (!$field->hasClass('fluent__localised-field')) {
            $field
                ->setTitle(DBField::create_field('HTMLFragment', $tooltip . $field->Title()))
                ->addExtraClass('fluent__localised-field');
            
            $rightTitle = $field->RightTitle();
            if (!empty($rightTitle) && !(is_a($rightTitle, DBField::class))) {
                $field->setRightTitle(DBField::create_field(DBHTMLVarchar::class, $rightTitle));
            }
        }
        
        
        //Update the publish date field to have the icon
        $field = $fields->dataFieldByName('PublishOnDate');
        if (!$field->hasClass('fluent__localised-field')) {
            $field
                ->setTitle(DBField::create_field('HTMLFragment', $tooltip . $field->Title()))
                ->addExtraClass('fluent__localised-field');
        }
        
        
        //Update the unpublish date field to have the icon
        $field = $fields->dataFieldByName('UnPublishOnDate');
        if (!$field->hasClass('fluent__localised-field')) {
            $field
                ->setTitle(DBField::create_field('HTMLFragment', $tooltip . $field->Title()))
                ->addExtraClass('fluent__localised-field');
        }
    }
    
    /**
     * Clears the workflow fields when localizing
     */
    public function onBeforeWrite()
    {
        if (Versioned::get_stage() === Versioned::DRAFT && !$this->owner->existsInLocale()) {
            $this->owner->PublishJobID = 0;
            $this->owner->UnPublishJobID = 0;
        }
        
        parent::onBeforeWrite();
    }
}
