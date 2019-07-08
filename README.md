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
Embargos and Workflow Instances will be created uniquely per locale on a page.

Use the following extension

```yml
Page:
    extensions:
        - WebbuildersGroup\FluentWorkflow\Extensions\FluentWorkflowEmbargoExpiryExtension
```