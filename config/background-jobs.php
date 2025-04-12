<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Allowed Background Jobs
    |--------------------------------------------------------------------------
    |
    | List of allowed job classes and their methods that can be executed
    | as background jobs. Format: 'ClassName' => ['method1', 'method2']
    |
    */
    'allowed_jobs' => [
        'App\Jobs\ExampleJob' => [
            'handle',
            'process',
            'notify'
        ],
        'App\Jobs\TestJob' => [
            'success',
            'eventualSuccess',
            'failure',
            'longRunning',
            'withPriority',
            'delayed'
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the retry mechanism for failed jobs
    |
    */
    'retry' => [
        'max_attempts' => 3,
        'delay_seconds' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the logging settings for background jobs
    |
    */
    'logging' => [
        'main_log' => storage_path('logs/background_jobs.log'),
        'error_log' => storage_path('logs/background_jobs_errors.log'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Priority Settings
    |--------------------------------------------------------------------------
    |
    | Configure job priority levels (1 highest, 5 lowest)
    |
    */
    'priorities' => [
        'default' => 3,
        'levels' => [1, 2, 3, 4, 5],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Additional security configurations
    |
    */
    'security' => [
        'validate_inputs' => true,
        'sanitize_parameters' => true,
    ],
]; 