<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Log;

class ExampleJob
{
    /**
     * Process a sample task
     *
     * @param string $data
     * @return bool
     */
    public function handle(string $data): bool
    {
        Log::info("Processing example job with data: {$data}");
        
        // Simulate some work
        sleep(2);
        
        // Log completion
        Log::info("Example job completed successfully");
        
        return true;
    }

    /**
     * Retry handler for failed jobs
     *
     * @param string $data
     * @return bool
     */
    public function retry(string $data): bool
    {
        Log::info("Retrying example job with data: {$data}");
        return $this->handle($data);
    }
} 