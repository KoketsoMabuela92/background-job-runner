<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BackgroundJobRunner;

class JobCreateCommand extends Command
{
    protected $signature = 'job:create 
                          {job_class : The full class name of the job}
                          {method : The method to execute}
                          {parameters : The parameters in JSON format}
                          {--priority=3 : Job priority (1-5)}
                          {--delay=0 : Delay in seconds before execution}';

    protected $description = 'Create a new background job';

    protected BackgroundJobRunner $jobRunner;

    public function __construct(BackgroundJobRunner $jobRunner)
    {
        parent::__construct();
        $this->jobRunner = $jobRunner;
    }

    public function handle()
    {
        try {
            $jobClass = $this->argument('job_class');
            $method = $this->argument('method');
            $parameters = json_decode($this->argument('parameters'), true);
            $priority = $this->option('priority');
            $delay = $this->option('delay');

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error('Invalid JSON parameters');
                return 1;
            }

            $job = $this->jobRunner->create(
                $jobClass,
                $method,
                $parameters,
                $priority,
                $delay
            );

            $this->info("Created job {$job->id}");
            $this->line("Status: {$job->status}");
            $this->line("Priority: {$job->priority}");
            if ($delay > 0) {
                $this->line("Scheduled for: {$job->scheduled_at}");
            }

            return 0;
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return 1;
        }
    }
} 