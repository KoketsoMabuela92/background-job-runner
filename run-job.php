<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

if ($argc < 3) {
    echo "Usage: php run-job.php ClassName methodName [param1,param2,...] [priority] [delay]\n";
    exit(1);
}

$class = $argv[1];
$method = $argv[2];
$params = isset($argv[3]) ? explode(',', $argv[3]) : [];
$priority = isset($argv[4]) ? (int)$argv[4] : null;
$delay = isset($argv[5]) ? (int)$argv[5] : 0;

try {
    $runner = new App\Services\BackgroundJobRunner();
    $result = $runner->run($class, $method, $params, $priority, $delay);
    
    if ($result) {
        echo "Job completed successfully\n";
        exit(0);
    } else {
        echo "Job failed\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
} 