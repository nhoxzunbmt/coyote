<?php

namespace Coyote\Events;

use Illuminate\Queue\SerializesModels;
use Coyote\Job;

class JobWasDeleted extends Event
{
    use SerializesModels;

    /**
     * @var array
     */
    public $job;

    /**
     * Create a new event instance.
     *
     * @param Job $job
     */
    public function __construct(Job $job)
    {
        $this->job = $job->toArray();
    }
}
