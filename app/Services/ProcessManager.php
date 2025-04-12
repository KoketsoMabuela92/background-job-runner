<?php

namespace App\Services;

use Symfony\Component\Process\Process;
use App\Models\BackgroundJob;
use Illuminate\Support\Facades\Log;

class ProcessManager
{
    /**
     * Start a background process for a job
     */
    public function startProcess(BackgroundJob $job): void
    {
        $command = [
            'php',
            base_path('artisan'),
            'job:run',
            $job->id
        ];

        $process = new Process($command);
        $process->setWorkingDirectory(base_path());
        $process->start();

        $job->update([
            'process_id' => $process->getPid(),
            'status' => 'running',
            'started_at' => now()
        ]);

        Log::info("Started process {$process->getPid()} for job {$job->id}");
    }

    /**
     * Kill a running process
     */
    public function killProcess(BackgroundJob $job): bool
    {
        if (!$job->process_id) {
            return false;
        }

        $process = new Process(['kill', '-9', (string) $job->process_id]);
        $process->run();

        return $process->isSuccessful();
    }

    /**
     * Check if a process is still running
     */
    public function isProcessRunning(BackgroundJob $job): bool
    {
        if (!$job->process_id) {
            return false;
        }

        $process = new Process(['ps', '-p', (string) $job->process_id]);
        $process->run();

        return $process->isSuccessful();
    }
} 