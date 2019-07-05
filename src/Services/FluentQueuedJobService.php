<?php

namespace WebbuildersGroup\FluentWorkflow\Services;

use Exception;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Security;
use TractorCow\Fluent\Model\Locale;
use TractorCow\Fluent\State\FluentState;
use Symbiote\QueuedJobs\Services\QueuedJob;
use Symbiote\QueuedJobs\Services\QueuedJobService;
use Symbiote\QueuedJobs\DataObjects\QueuedJobDescriptor;

class FluentQueuedJobService extends QueuedJobService
{
    public function queueJob(QueuedJob $job, $startAfter = null, $userId = null, $queueName = null)
    {
        $job->__set('locale', Locale::getCurrentLocale());
        return parent::queueJob($job, $startAfter, $userId, $queueName);
    }

    public function runJob($jobId)
    {
        // first retrieve the descriptor
        $jobDescriptor = DataObject::get_by_id(
            QueuedJobDescriptor::class,
            (int)$jobId
        );
        if (!$jobDescriptor) {
            throw new Exception("$jobId is invalid");
        }
        return FluentState::singleton()->withState(function ($state) use ($jobDescriptor) {
            $state->setLocale(unserialize($jobDescriptor->SavedJobData)->locale->Locale);
            return parent::runJob($jobDescriptor->ID);
        });
    }
}
