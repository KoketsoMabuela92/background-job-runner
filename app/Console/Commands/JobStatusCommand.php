<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BackgroundJob;

class JobStatusCommand extends Command
{
    protected $signature = 'job:status {job_id : The ID of the job}';

    protected $description = 'Show the status of a background job';

    public function handle()
    {
        try {
            $jobId = $this->argument('job_id');
            $job = BackgroundJob::findOrFail($jobId);

            $this->info("\nJob Details:");
            $this->table(
                ['Field', 'Value'],
                [
                    ['ID', $job->id],
                    ['Class', $job->job_class],
                    ['Method', $job->method],
                    ['Status', $job->status],
                    ['Priority', $job->priority],
                    ['Attempts', $job->attempts],
                    ['Created At', $job->created_at],
                    ['Started At', $job->started_at ?? 'Not started'],
                    ['Completed At', $job->completed_at ?? 'Not completed'],
                    ['Parameters', json_encode($job->parameters, JSON_PRETTY_PRINT)],
                    ['Error', $job->error ?? 'None']
                ]
            );

            if ($job->status === 'failed' && $job->error) {
                $this->error("\nError Details:");
                $this->line($job->error);
            }

            return 0;
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return 1;
        }
    }
} 