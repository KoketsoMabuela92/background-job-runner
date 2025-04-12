<?php

use Symfony\Component\Process\Process;

if (!function_exists('runBackgroundJob')) {
    /**
     * Run a job in the background
     *
     * @param string $class The fully qualified class name
     * @param string $method The method to execute
     * @param array $params Parameters for the method
     * @param int|null $priority Job priority (1-5, 1 being highest)
     * @param int $delay Delay in seconds before execution
     * @return bool
     */
    function runBackgroundJob(string $class, string $method, array $params = [], ?int $priority = null, int $delay = 0): bool
    {
        $phpBinary = PHP_BINARY;
        $scriptPath = base_path('run-job.php');
        
        // Convert parameters to comma-separated string
        $paramsString = implode(',', $params);
        
        // Build the command
        $command = [
            $phpBinary,
            $scriptPath,
            $class,
            $method,
            $paramsString
        ];

        if ($priority !== null) {
            $command[] = (string)$priority;
        }
        
        if ($delay > 0) {
            $command[] = (string)$delay;
        }

        // Create and configure the process
        $process = new Process($command);
        $process->setWorkingDirectory(base_path());
        
        // Run the process in background
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows
            $process->start();
        } else {
            // Unix-based systems
            $process->start(null, [
                'bypass_shell' => true,
            ]);
        }

        return true;
    }
} 