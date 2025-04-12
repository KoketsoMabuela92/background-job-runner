<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Jobs\TestJob;

echo "Running Background Job Examples...\n\n";

// Example 1: Simple successful job
echo "1. Running a simple successful job...\n";
runBackgroundJob(TestJob::class, 'success', ['message' => 'Hello, World!']);
echo "Job queued successfully.\n\n";

// Example 2: Job with retry logic
echo "2. Running a job that will fail and retry...\n";
runBackgroundJob(TestJob::class, 'eventualSuccess', ['attempts' => 0]);
echo "Job queued (will fail first time).\n\n";

// Example 3: Job with different priorities
echo "3. Running jobs with different priorities...\n";
runBackgroundJob(TestJob::class, 'withPriority', ['priority' => 5], 5); // Low priority
runBackgroundJob(TestJob::class, 'withPriority', ['priority' => 1], 1); // High priority
runBackgroundJob(TestJob::class, 'withPriority', ['priority' => 3], 3); // Medium priority
echo "Jobs with different priorities queued.\n\n";

// Example 4: Delayed job
echo "4. Running a delayed job...\n";
runBackgroundJob(TestJob::class, 'delayed', ['delay' => 10], null, 10);
echo "Delayed job queued (will run after 10 seconds).\n\n";

// Example 5: Long-running job that can be cancelled
echo "5. Running a long-running job...\n";
runBackgroundJob(TestJob::class, 'longRunning', ['duration' => 30]);
echo "Long-running job queued (can be cancelled from dashboard).\n\n";

// Example 6: Job that will fail
echo "6. Running a job that will fail...\n";
runBackgroundJob(TestJob::class, 'failure', ['message' => 'This job will fail']);
echo "Failed job queued.\n\n";

echo "All example jobs have been queued. Check the dashboard at http://localhost:8000/jobs\n";
echo "You can also check the logs at:\n";
echo "- Main log: storage/logs/background_jobs.log\n";
echo "- Error log: storage/logs/background_jobs_errors.log\n"; 