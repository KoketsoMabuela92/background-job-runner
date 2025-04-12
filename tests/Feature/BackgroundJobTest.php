<?php

namespace Tests\Feature;

use App\Jobs\TestJob;
use App\Models\BackgroundJob;
use App\Services\BackgroundJobRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class BackgroundJobTest extends TestCase
{
    use RefreshDatabase;

    private BackgroundJobRunner $jobRunner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->jobRunner = app(BackgroundJobRunner::class);
        
        // Configure allowed jobs
        config(['background-jobs.allowed_jobs' => [
            TestJob::class => ['success', 'eventualSuccess', 'failure', 'longRunning', 'withPriority', 'delayed']
        ]]);
    }

    /** @test */
    public function it_can_create_and_run_a_successful_job()
    {
        $job = $this->jobRunner->create(TestJob::class, 'success', ['test' => 'data']);
        
        $this->assertEquals('pending', $job->status);
        $this->assertNull($job->started_at);
        
        $result = $this->jobRunner->run($job);
        $job->refresh();
        
        $this->assertTrue($result);
        $this->assertEquals('completed', $job->status);
        $this->assertNotNull($job->started_at);
        $this->assertNotNull($job->completed_at);
    }

    /** @test */
    public function it_handles_job_failure_and_retry()
    {
        $job = $this->jobRunner->create(TestJob::class, 'eventualSuccess', ['attempts' => 0]);
        
        // First attempt should fail
        $result = $this->jobRunner->run($job);
        $job->refresh();
        
        $this->assertFalse($result);
        $this->assertEquals('failed', $job->status);
        $this->assertEquals(1, $job->attempts);
        
        // Retry should succeed
        $newJob = $this->jobRunner->create(
            TestJob::class,
            'eventualSuccess',
            ['attempts' => 1],
            $job->priority
        );
        
        $result = $this->jobRunner->run($newJob);
        $newJob->refresh();
        
        $this->assertTrue($result);
        $this->assertEquals('completed', $newJob->status);
    }

    /** @test */
    public function it_respects_job_priorities()
    {
        // Create jobs with different priorities
        $lowPriority = $this->jobRunner->create(TestJob::class, 'withPriority', ['priority' => 5], 5);
        $highPriority = $this->jobRunner->create(TestJob::class, 'withPriority', ['priority' => 1], 1);
        $mediumPriority = $this->jobRunner->create(TestJob::class, 'withPriority', ['priority' => 3], 3);
        
        $pendingJobs = BackgroundJob::pending()->get();
        
        $this->assertEquals($highPriority->id, $pendingJobs[0]->id);
        $this->assertEquals($mediumPriority->id, $pendingJobs[1]->id);
        $this->assertEquals($lowPriority->id, $pendingJobs[2]->id);
    }

    /** @test */
    public function it_handles_delayed_jobs()
    {
        $delay = 5;
        $job = $this->jobRunner->create(TestJob::class, 'delayed', ['delay' => $delay], null, $delay);
        
        $this->assertEquals('pending', $job->status);
        $this->assertTrue($job->scheduled_at->isFuture());
        
        // Simulate time passing
        $this->travel($delay)->seconds();
        
        $result = $this->jobRunner->run($job);
        $job->refresh();
        
        $this->assertTrue($result);
        $this->assertEquals('completed', $job->status);
    }

    /** @test */
    public function it_can_cancel_running_jobs()
    {
        $job = $this->jobRunner->create(TestJob::class, 'longRunning', ['test' => 'data']);
        
        // Start the job
        $job->update(['status' => 'running', 'process_id' => getmypid()]);
        
        $result = $this->jobRunner->cancel($job);
        $job->refresh();
        
        $this->assertTrue($result);
        $this->assertEquals('cancelled', $job->status);
    }

    /** @test */
    public function it_validates_allowed_jobs()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Job class or method not allowed');
        
        $this->jobRunner->create(TestJob::class, 'nonexistentMethod', []);
    }
} 