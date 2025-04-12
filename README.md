<img width="1487" alt="Screenshot 2025-04-13 at 00 49 24" src="https://github.com/user-attachments/assets/4d8af5bd-7432-4a96-b68e-f4287faf7b8f" />



# Laravel Background Job Runner

A custom background job processing system for Laravel that operates independently of Laravel's built-in queue system. This system provides a flexible, secure, and platform-independent way to execute PHP classes as background jobs.

## Features

- Execute PHP classes as background jobs via CLI
- Platform-independent (works on both Windows and Unix-based systems)
- Comprehensive logging system
- Configurable retry mechanism for failed jobs
- Job prioritization
- Delayed job execution
- Security features (input validation and sanitization)
- Cross-platform compatibility
- Automatic job processing via scheduler
- Real-time job status monitoring

## Installation

1. Clone the repository:
```bash
git clone <repository-url>
cd background-job-runner
```

2. Install dependencies:
```bash
composer install
```

3. Copy the environment file:
```bash
cp .env.example .env
```

4. Generate application key:
```bash
php artisan key:generate
```

5. Run migrations:
```bash
php artisan migrate
```

## Configuration

The system can be configured through the `config/background-jobs.php` file:

```php
return [
    'allowed_jobs' => [
        'App\Jobs\ExampleJob' => ['handle', 'retry'],
        // Add your job classes and allowed methods here
    ],
    'retry' => [
        'max_attempts' => 3,
        'delay_seconds' => 60,
    ],
    // ... other configurations
];
```

## Running the Scheduler

The background job runner uses Laravel's scheduler to process jobs automatically. Here's how to set it up:

### Development

For local development, start the scheduler in the background:
```bash
php artisan schedule:work &
```

### Production

For production environments, you should set up the scheduler to run automatically. Here are the recommended approaches:

#### Using Supervisor (Linux)
Create a supervisor configuration file `/etc/supervisor/conf.d/laravel-scheduler.conf`:
```ini
[program:laravel-scheduler]
process_name=%(program_name)s
command=php /path/to/your/project/artisan schedule:work
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/your/project/storage/logs/scheduler.log
```

Then run:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-scheduler
```

#### Using Systemd (Linux)
Create a service file `/etc/systemd/system/laravel-scheduler.service`:
```ini
[Unit]
Description=Laravel Scheduler
After=network.target

[Service]
User=www-data
ExecStart=/usr/bin/php /path/to/your/project/artisan schedule:work
Restart=always

[Install]
WantedBy=multi-user.target
```

Then run:
```bash
sudo systemctl enable laravel-scheduler
sudo systemctl start laravel-scheduler
```

#### Using Launch Agents (macOS)
Create a launch agent configuration in `~/Library/LaunchAgents/com.background-job-runner.scheduler.plist`:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
    <key>Label</key>
    <string>com.background-job-runner.scheduler</string>
    <key>ProgramArguments</key>
    <array>
        <string>/usr/local/bin/php</string>
        <string>artisan</string>
        <string>schedule:work</string>
    </array>
    <key>RunAtLoad</key>
    <true/>
    <key>KeepAlive</key>
    <true/>
    <key>WorkingDirectory</key>
    <string>/path/to/your/project</string>
</dict>
</plist>
```

Then run:
```bash
launchctl load ~/Library/LaunchAgents/com.background-job-runner.scheduler.plist
```

## Usage

### Using the Helper Function

```php
// Basic usage
runBackgroundJob(ExampleJob::class, 'handle', ['message' => 'Hello World']);

// With priority (1-5, 1 being highest) and delay (in seconds)
runBackgroundJob(ExampleJob::class, 'handle', ['message' => 'Delayed message'], 1, 60);

// With chained jobs
runBackgroundJob(ExampleJob::class, 'handle', ['step' => 1])
    ->chain(AnotherJob::class, 'process', ['step' => 2]);
```

### Using the Web Interface

1. Access the dashboard at `/jobs`
2. Click "Create New Job" button
3. Fill in the job details:
   - Select Job Class
   - Choose Method
   - Enter Parameters (JSON format)
   - Set Priority (1-5, 1 being highest)
   - Set Delay (in seconds, optional)
4. Click "Schedule Job" to create the job

### Using Artisan Commands

```bash
# Create a new job
php artisan job:create "App\Jobs\ExampleJob" handle '{"message": "test"}' --priority=1 --delay=60

# Run pending jobs manually (normally handled by scheduler)
php artisan job:run-pending

# Run pending jobs with verbose output
php artisan job:run-pending -v

# View job status
php artisan job:status {job_id}

# Cancel a job (works for both running and pending jobs)
php artisan job:cancel {job_id}

# Retry a failed job
php artisan job:retry {job_id}
```

### Running Jobs Manually

While the scheduler automatically processes jobs every minute, you can also run pending jobs manually:

1. **Process all pending jobs**:
   ```bash
   php artisan job:run-pending
   ```

2. **Process with verbose output** (shows job details and progress):
   ```bash
   php artisan job:run-pending -v
   ```

3. **Check job status after processing**:
   ```bash
   php artisan job:status {job_id}
   ```

This is useful for:
- Testing jobs during development
- Immediate processing without waiting for the scheduler
- Debugging job execution with verbose output
- Processing specific jobs on demand

Note: Manual processing doesn't interfere with the scheduler. The scheduler will continue to process any remaining pending jobs according to its schedule.

### Creating a Job Class

```php
namespace App\Jobs;

use App\Jobs\Traits\Chainable;

class MyJob
{
    use Chainable; // Add this to support job chaining

    /**
     * Define which exceptions should be retried and how many times
     */
    protected array $retryableExceptions = [
        RuntimeException::class => 3,
        InvalidArgumentException::class => 1
    ];

    /**
     * Job method - receives parameters as a single array
     */
    public function handle(array $parameters)
    {
        // Access parameters as array keys
        $message = $parameters['message'] ?? 'default';
        
        // Job logic here
        return true;
    }
}
```

## Logging

The system provides comprehensive logging:
- Main job logs: `storage/logs/laravel.log`
- Scheduler logs: `storage/logs/scheduler.log`
- Error logs: `storage/logs/background_jobs_errors.log`

## Job Statuses

Jobs can have the following statuses:
- `pending`: Job is waiting to be processed
- `running`: Job is currently being executed
- `completed`: Job finished successfully
- `failed`: Job failed and exceeded retry attempts
- `cancelled`: Job was manually cancelled

## Security

The system includes several security features:
- Only pre-configured job classes and methods can be executed
- Input validation and sanitization
- Secure process execution using Symfony Process
- Parameter sanitization (configurable)

## Error Handling

The system includes a robust error handling mechanism:
- Automatic retry for failed jobs
- Configurable maximum retry attempts
- Detailed error logging
- Exception type-based retry logic
- Job-specific retry rules

## Web Interface

The system includes a comprehensive web-based dashboard for managing and monitoring background jobs.

## Dashboard Features

- **Job Overview**: View all jobs with their current status, priority, and execution times
- **Real-time Status**: Monitor job status updates in real-time
- **Job Management**:
  - Cancel running jobs
  - Retry failed jobs
  - View job details and parameters
  - Schedule new jobs with priorities and delays

### Job Details View

Each job has a detailed view showing:
- Job class and method
- Current status and execution progress
- Input parameters
- Start and completion times
- Number of retry attempts
- Error messages (if any)
- Chained jobs (if any)

### Log Viewer

The web interface includes a dedicated log viewer that shows:
- Main job execution logs
- Error logs with stack traces
- Filtering options by:
  - Job status
  - Date range
  - Job class
  - Priority level

### Access and Routes

The web interface is accessible through the following routes:

```php
// Main dashboard
/jobs

// Job details
/jobs/{id}

// Job logs
/jobs/{id}/logs

// Actions
/jobs/{id}/cancel  // POST
/jobs/{id}/retry   // POST
```

### Security

The web interface includes:
- Authentication middleware
- CSRF protection
- Input validation
- Action authorization

## Contributing

Contact:
Koketso Mabuela
glenton92@gmail.com

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details.
