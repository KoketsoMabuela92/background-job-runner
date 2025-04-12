<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Log;
use App\Jobs\Traits\Chainable;
use Exception;
use InvalidArgumentException;
use RuntimeException;

class TestJob
{
    use Chainable;

    /**
     * Exception types that should be retried
     */
    protected array $retryableExceptions = [
        RuntimeException::class => 3, // Retry 3 times
        InvalidArgumentException::class => 1 // Retry once
    ];

    /**
     * Get retryable exceptions and their max attempts
     */
    public function getRetryableExceptions(): array
    {
        return $this->retryableExceptions;
    }

    /**
     * Successful job execution with chaining example
     */
    public function success($data)
    {
        Log::info("Processing successful job with data: " . json_encode($data));
        sleep(2); // Simulate work

        // Example of chaining another job
        $this->chain(TestJob::class, 'delayed', ['delay' => 5]);
        
        return true;
    }

    /**
     * Job that fails on first attempt but succeeds on retry
     */
    public function eventualSuccess($data)
    {
        Log::info("Processing eventual success job with data: " . json_encode($data));
        
        if (!isset($data['attempts']) || $data['attempts'] < 1) {
            throw new RuntimeException("Simulated failure, will retry up to 3 times");
        }
        
        return true;
    }

    /**
     * Job that always fails with non-retryable exception
     */
    public function failure($data)
    {
        Log::error("Processing failing job with data: " . json_encode($data));
        throw new Exception("Simulated permanent failure - not retryable");
    }

    /**
     * Job that fails with retryable exception
     */
    public function retryableFailure($data)
    {
        Log::info("Processing job that will fail with retryable exception");
        throw new InvalidArgumentException("Simulated failure - will retry once");
    }

    /**
     * Long-running job that can be cancelled
     */
    public function longRunning($data)
    {
        Log::info("Starting long-running job with data: " . json_encode($data));
        
        for ($i = 0; $i < 30; $i++) {
            sleep(1);
            Log::info("Long-running job progress: {$i}/30");
        }
        
        return true;
    }

    /**
     * Job with different priority levels
     */
    public function withPriority($data)
    {
        $priority = $data['priority'] ?? 3;
        Log::info("Processing job with priority {$priority}");
        sleep(1);
        return true;
    }

    /**
     * Delayed execution job
     */
    public function delayed($data)
    {
        $delay = $data['delay'] ?? 0;
        Log::info("Processing delayed job after {$delay} seconds");
        return true;
    }
} 