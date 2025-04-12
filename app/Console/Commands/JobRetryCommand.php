<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BackgroundJobRunner;
use App\Models\BackgroundJob;

class JobRetryCommand extends Command
{
    protected $signature = 'job:retry {job_id : The ID of the job to retry}';

    protected $description = 'Retry a failed background job';

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

            if ($job->status !== 'failed') {
                $this->error("Job {$jobId} is not in failed status (current status: {$job->status})");
                return 1;
            }

            $job->update([
                'status' => 'pending',
                'error' => null,
                'scheduled_at' => now()
            ]);

            $this->info("Job {$jobId} has been queued for retry");
            $this->line("Current attempts: {$job->attempts}");
            
            return 0;
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return 1;
        }
    }
} 