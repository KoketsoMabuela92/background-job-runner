<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BackgroundJobRunner;
use App\Models\BackgroundJob;

class JobCancelCommand extends Command
{
    protected $signature = 'job:cancel {job_id : The ID of the job to cancel}';

    protected $description = 'Cancel a running background job';

    protected BackgroundJobRunner $jobRunner;

    public function __construct(BackgroundJobRunner $jobRunner)
    {
        parent::__construct();
        $this->jobRunner = $jobRunner;
    }

    public function handle()
    {
        try {
            $jobId = $this->argument('job_id');
            $job = BackgroundJob::findOrFail($jobId);

            if ($job->status !== 'running') {
                $this->error("Job {$jobId} is not running (current status: {$job->status})");
                return 1;
            }

            if ($this->jobRunner->cancel($job)) {
                $this->info("Successfully cancelled job {$jobId}");
                return 0;
            }

            $this->error("Failed to cancel job {$jobId}");
            return 1;
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return 1;
        }
    }
} 