<?php

namespace App\Services;

use App\Models\BackgroundJob;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;
use Exception;

class BackgroundJobRunner
{
    private $config;
    private $job;

    public function __construct()
    {
        $this->config = Config::get('background-jobs');
    }

    /**
     * Create a new background job
     */
    public function create(string $class, string $method, array $params = [], ?int $priority = null, int $delay = 0): BackgroundJob
    {
        $this->validateJob($class, $method);
        $this->validateParams($params);

        $scheduledAt = $delay > 0 ? Carbon::now()->addSeconds($delay) : null;
        
        return BackgroundJob::create([
            'job_class' => $class,
            'method' => $method,
            'parameters' => $params,
            'priority' => $priority ?? $this->config['priorities']['default'],
            'delay' => $delay,
            'scheduled_at' => $scheduledAt,
            'status' => 'pending'
        ]);
    }

    /**
     * Run a background job
     */
    public function run(BackgroundJob $job): bool
    {
        $this->job = $job;

        try {
            // Check if job should be delayed
            if ($job->scheduled_at && $job->scheduled_at->isFuture()) {
                return true;
            }

            // Check if job is already completed or cancelled
            if (in_array($job->status, ['completed', 'cancelled'])) {
                return true;
            }

            // Store current process ID
            $job->update(['process_id' => getmypid()]);
            
            $job->markAsRunning();
            $this->logJobStart();

            $instance = app($job->job_class);
            // Pass parameters as a single array argument
            $result = $instance->{$job->method}($job->parameters ?? []);

            // Clear process ID on completion
            $job->update(['process_id' => null]);

            // Only mark as completed if the job returns true
            if ($result === true) {
                $job->markAsCompleted();
                $this->logJobCompletion(true);
                return true;
            }

            // If job returns false, mark as failed
            $job->markAsFailed('Job returned false');
            return false;

        } catch (Exception $e) {
            // Clear process ID on error
            $job->update(['process_id' => null]);
            
            $this->logError($e);
            $job->markAsFailed($e->getMessage());
            
            if ($this->shouldRetry()) {
                return $this->retry();
            }

            return false;
        }
    }

    /**
     * Cancel a running or pending job
     */
    public function cancel(BackgroundJob $job): bool
    {
        if (!$job->canBeCancelled()) {
            Log::error("Cannot cancel job {$job->id}: Job status is {$job->status}");
            return false;
        }

        try {
            // For running jobs with a process ID, try to kill the process
            if ($job->status === 'running' && $job->process_id) {
                // Send SIGTERM to the process
                $process = Process::fromShellCommandline("kill -15 {$job->process_id}");
                $process->run();

                if (!$process->isSuccessful()) {
                    Log::error("Failed to send SIGTERM to process {$job->process_id}: " . $process->getErrorOutput());
                    
                    // Try SIGKILL as a last resort
                    $process = Process::fromShellCommandline("kill -9 {$job->process_id}");
                    $process->run();
                    
                    if (!$process->isSuccessful()) {
                        Log::error("Failed to send SIGKILL to process {$job->process_id}: " . $process->getErrorOutput());
                        return false;
                    }
                }
            }

            // Update job status
            $job->update([
                'status' => 'cancelled',
                'completed_at' => Carbon::now(),
                'process_id' => null,
                'error' => $job->status === 'running' ? 'Job cancelled while running' : 'Job cancelled before execution'
            ]);

            Log::info("Successfully cancelled job {$job->id} (Status was: {$job->status}" . 
                     ($job->process_id ? ", PID: {$job->process_id})" : ")"));
            return true;
        } catch (Exception $e) {
            Log::error("Failed to cancel job {$job->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Validate if the job class and method are allowed
     */
    private function validateJob(string $class, string $method): void
    {
        if (!isset($this->config['allowed_jobs'][$class]) || 
            !in_array($method, $this->config['allowed_jobs'][$class])) {
            throw new Exception("Job class or method not allowed: {$class}@{$method}");
        }
    }

    /**
     * Validate and sanitize parameters if configured
     */
    private function validateParams(array $params): void
    {
        if ($this->config['security']['sanitize_parameters']) {
            array_walk_recursive($params, function (&$item) {
                $item = filter_var($item, FILTER_SANITIZE_STRING);
            });
        }
    }

    /**
     * Check if we should retry the job
     */
    private function shouldRetry(): bool
    {
        return $this->job->attempts < $this->config['retry']['max_attempts'];
    }

    /**
     * Retry a failed job
     */
    private function retry(): bool
    {
        $this->job->incrementAttempts();
        
        // Create a new delayed job for retry
        return $this->create(
            $this->job->job_class,
            $this->job->method,
            $this->job->parameters ?? [],
            $this->job->priority,
            $this->config['retry']['delay_seconds']
        ) !== null;
    }

    /**
     * Log job start
     */
    private function logJobStart(): void
    {
        $message = sprintf(
            "Starting job ID %d: %s@%s with parameters: %s",
            $this->job->id,
            $this->job->job_class,
            $this->job->method,
            json_encode($this->job->parameters)
        );
        
        Log::channel('daily')->info($message);
    }

    /**
     * Log job completion
     */
    private function logJobCompletion(bool $success): void
    {
        $status = $success ? 'completed successfully' : 'failed';
        $message = sprintf(
            "Job ID %d %s@%s %s",
            $this->job->id,
            $this->job->job_class,
            $this->job->method,
            $status
        );
        
        Log::channel('daily')->info($message);
    }

    /**
     * Log error details
     */
    private function logError(Exception $exception): void
    {
        $message = sprintf(
            "Error in job ID %d %s@%s: %s\n%s",
            $this->job->id,
            $this->job->job_class,
            $this->job->method,
            $exception->getMessage(),
            $exception->getTraceAsString()
        );
        
        Log::channel('daily')->error($message);
        
        // Also log to the dedicated error log
        file_put_contents(
            $this->config['logging']['error_log'],
            date('[Y-m-d H:i:s] ') . $message . PHP_EOL,
            FILE_APPEND
        );
    }
} 