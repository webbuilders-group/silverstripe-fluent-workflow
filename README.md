Fluent Workflow
=================
Adds compatibility to symbiote/silverstripe-advancedworkflow for tractorcow/silverstripe-fluent

## Maintainer Contact
* Robert LeCreux ([RobertLeCreux](https://github.com/RobertLeCreux))

## Requirements
* SilverStripe CMS 4.0+
* tractorcow/silverstripe-fluent ~4.2
* symbiote/silverstripe-advancedworkflow ~5.2

## Installation
```
composer require webbuilders-group/silverstripe-fluent-workflow
```


## Usage
Embargos and Workflow Instances will be created uniquely per locale on a page. Note the data object you are assigning these classes to must have the `TractorCow\Fluent\Extension\FluentExtension` extension (or one of it's subclasses).

For workflows use the following extension:
```yml
MyDataObject:
    extensions:
        - WebbuildersGroup\FluentWorkflow\Extensions\FluentWorkflowApplicable
```


For embargo's use the following extension:
```yml
MyDataObject:
    extensions:
        - WebbuildersGroup\FluentWorkflow\Extensions\FluentWorkflowEmbargoExpiryExtension
```