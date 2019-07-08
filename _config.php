<?php

use SilverStripe\CMS\Model\SiteTree;
use Symbiote\AdvancedWorkflow\Extensions\WorkflowApplicable;

SiteTree::remove_extension(WorkflowApplicable::class);
