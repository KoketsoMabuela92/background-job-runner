<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BackgroundJobRunner;
use App\Models\BackgroundJob;

class JobRunPendingCommand extends Command
{
    protected $signature = 'job:run-pending {--limit=10 : Maximum number of jobs to process}';

    protected $description = 'Run pending background jobs';

    protected BackgroundJobRunner $jobRunner;

    public function __construct(BackgroundJobRunner $jobRunner)
    {
        parent::__construct();
        $this->jobRunner = $jobRunner;
    }

    public function handle()
    {
        try {
            $limit = $this->option('limit');
            
            $this->info('Checking for pending jobs...');
            
            $query = BackgroundJob::query()
                ->where('status', 'pending')
                ->where(function ($query) {
                    $query->whereNull('scheduled_at')
                          ->orWhere('scheduled_at', '<=', now());
                })
                ->orderBy('priority')
                ->orderBy('created_at')
                ->limit($limit);

            // Log the SQL query for debugging
            \Log::info('Job query: ' . $query->toSql());
            \Log::info('Job query bindings: ' . json_encode($query->getBindings()));
            
            $jobs = $query->get();

            $this->info('Found ' . $jobs->count() . ' pending jobs');
            
            if ($jobs->isEmpty()) {
                $this->info('No pending jobs to process');
                return 0;
            }

            $this->info("Processing {$jobs->count()} pending jobs...");

            foreach ($jobs as $job) {
                $this->line("\nProcessing job {$job->id}...");
                $this->line("Class: {$job->job_class}");
                $this->line("Method: {$job->method}");
                $this->line("Priority: {$job->priority}");
                $this->line("Scheduled At: " . ($job->scheduled_at ?? 'immediate'));
                
                $result = $this->jobRunner->run($job);
                
                if ($result) {
                    $this->info("✓ Job {$job->id} completed successfully");
                } else {
                    $this->warn("! Job {$job->id} failed or was rescheduled");
                }
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('Error running pending jobs: ' . $e->getMessage());
            \Log::error('Error in job:run-pending command: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return 1;
        }
    }
} 