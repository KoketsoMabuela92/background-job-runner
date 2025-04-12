<?php

namespace App\Http\Controllers;

use App\Models\BackgroundJob;
use App\Services\BackgroundJobRunner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BackgroundJobController extends Controller
{
    protected BackgroundJobRunner $jobRunner;

    public function __construct(BackgroundJobRunner $jobRunner)
    {
        $this->jobRunner = $jobRunner;
    }

    public function index()
    {
        $jobs = BackgroundJob::latest()->paginate(10);
        
        $stats = [
            'pending' => BackgroundJob::where('status', 'pending')->count(),
            'running' => BackgroundJob::where('status', 'running')->count(),
            'completed' => BackgroundJob::where('status', 'completed')->count(),
            'failed' => BackgroundJob::where('status', 'failed')->count(),
            'cancelled' => BackgroundJob::where('status', 'cancelled')->count(),
        ];

        return view('jobs.index', compact('jobs', 'stats'));
    }

    public function create()
    {
        return view('jobs.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'job_class' => 'required|string',
            'method' => 'required|string',
            'parameters' => 'nullable|json',
            'priority' => 'required|integer|min:1|max:5',
            'delay' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('jobs.create')
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $parameters = $request->parameters ? json_decode($request->parameters, true) : [];
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return redirect()
                    ->route('jobs.create')
                    ->withErrors(['parameters' => 'Invalid JSON format'])
                    ->withInput();
            }

            $job = $this->jobRunner->create(
                $request->job_class,
                $request->method,
                $parameters,
                $request->priority,
                $request->delay
            );

            return redirect()
                ->route('jobs.show', $job)
                ->with('success', 'Job created successfully');
        } catch (\Exception $e) {
            return redirect()
                ->route('jobs.create')
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();
        }
    }

    public function show(BackgroundJob $job)
    {
        return view('jobs.show', compact('job'));
    }

    public function logs(Request $request)
    {
        $jobId = $request->input('job_id');
        $query = BackgroundJob::query();
        
        if ($jobId) {
            $query->where('id', $jobId);
        }
        
        $logs = $query->latest()->paginate(50);
        return view('jobs.logs', compact('logs'));
    }

    public function cancel(BackgroundJob $job)
    {
        try {
            if (!in_array($job->status, ['running', 'pending'])) {
                return back()->with('error', 'Only running or pending jobs can be cancelled.');
            }

            if ($job->status === 'pending') {
                $job->update([
                    'status' => 'cancelled',
                    'completed_at' => now()
                ]);
                return back()->with('success', 'Pending job cancelled successfully.');
            }

            if ($this->jobRunner->cancel($job)) {
                return back()->with('success', 'Running job cancelled successfully.');
            }

            return back()->with('error', 'Failed to cancel job.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error cancelling job: ' . $e->getMessage());
        }
    }

    public function retry(BackgroundJob $job)
    {
        try {
            if ($job->status !== 'failed') {
                return back()->with('error', 'Only failed jobs can be retried.');
            }

            $job->update([
                'status' => 'pending',
                'error' => null,
                'scheduled_at' => now()
            ]);

            return back()->with('success', 'Job queued for retry.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error retrying job: ' . $e->getMessage());
        }
    }
} 