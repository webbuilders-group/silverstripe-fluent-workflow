<?php

namespace WebbuildersGroup\FluentWorkflow\Services;

use SilverStripe\ORM\DataObject;
use Symbiote\QueuedJobs\DataObjects\QueuedJobDescriptor;
use Symbiote\QueuedJobs\Services\QueuedJob;
use Symbiote\QueuedJobs\Services\QueuedJobService;
use TractorCow\Fluent\Model\Locale;
use TractorCow\Fluent\State\FluentState;
use Exception;

class FluentQueuedJobService extends QueuedJobService
{
    public function queueJob(QueuedJob $job, $startAfter = null, $userId = null, $queueName = null)
    {
        $job->__set('Locale', Locale::getCurrentLocale()->Locale);
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
            $data = unserialize($jobDescriptor->SavedJobData);
            $state->setLocale((is_string($data->Locale) ? $data->Locale : $data->Locale->Locale));
            return parent::runJob($jobDescriptor->ID);
        });
    }
}
