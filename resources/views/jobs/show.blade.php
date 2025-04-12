@extends('layouts.app')

@section('title', 'Job Details')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold">Job Details #{{ $job->id }}</h1>
            <p class="text-gray-600 mt-1">{{ $job->job_class }}::{{ $job->method }}</p>
        </div>
        
        <div class="flex space-x-4">
            @if($job->status === 'running' || $job->status === 'pending')
                <form action="{{ route('jobs.cancel', $job) }}" method="POST">
                    @csrf
                    <button type="submit" 
                            class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg flex items-center"
                            onclick="return confirm('Are you sure you want to cancel this job?')">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Cancel Job
                    </button>
                </form>
            @endif

            @if($job->status === 'failed')
                <form action="{{ route('jobs.retry', $job) }}" method="POST">
                    @csrf
                    <button type="submit" 
                            class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg flex items-center"
                            onclick="return confirm('Are you sure you want to retry this job?')">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Retry Job
                    </button>
                </form>
            @endif

            <a href="{{ route('jobs.logs', ['job_id' => $job->id]) }}" 
               class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                View Logs
            </a>

            <a href="{{ route('jobs.index') }}" 
               class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Dashboard
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-6" role="alert">
        <span class="block sm:inline">{{ session('success') }}</span>
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6" role="alert">
        <span class="block sm:inline">{{ session('error') }}</span>
    </div>
    @endif

    <div class="bg-white shadow-lg rounded-lg overflow-hidden">
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Status Information</h3>
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Current Status</dt>
                            <dd>
                                <span class="mt-1 inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                    @if($job->status === 'completed') bg-green-100 text-green-800
                                    @elseif($job->status === 'failed') bg-red-100 text-red-800
                                    @elseif($job->status === 'running') bg-blue-100 text-blue-800
                                    @elseif($job->status === 'cancelled') bg-gray-100 text-gray-800
                                    @else bg-yellow-100 text-yellow-800
                                    @endif">
                                    {{ ucfirst($job->status) }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Priority</dt>
                            <dd class="mt-1">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                    @if($job->priority === 1) bg-red-100 text-red-800
                                    @elseif($job->priority === 2) bg-orange-100 text-orange-800
                                    @elseif($job->priority === 3) bg-yellow-100 text-yellow-800
                                    @elseif($job->priority === 4) bg-green-100 text-green-800
                                    @else bg-blue-100 text-blue-800
                                    @endif">
                                    Priority {{ $job->priority }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Attempts</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $job->attempts }}</dd>
                        </div>
                    </dl>
                </div>

                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Timing Information</h3>
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Created At</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $job->created_at->format('Y-m-d H:i:s') }}</dd>
                        </div>
                        @if($job->started_at)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Started At</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $job->started_at->format('Y-m-d H:i:s') }}</dd>
                        </div>
                        @endif
                        @if($job->completed_at)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Completed At</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $job->completed_at->format('Y-m-d H:i:s') }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>

            <div class="mt-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Parameters</h3>
                <pre class="bg-gray-50 p-4 rounded-lg overflow-x-auto text-sm">{{ json_encode($job->parameters, JSON_PRETTY_PRINT) }}</pre>
            </div>

            @if($job->error)
            <div class="mt-8">
                <h3 class="text-lg font-medium text-red-900 mb-4">Error Information</h3>
                <pre class="bg-red-50 p-4 rounded-lg overflow-x-auto text-sm text-red-900">{{ $job->error }}</pre>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection 