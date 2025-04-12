@extends('layouts.app')

@section('title', 'Create New Job')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Create New Job</h1>
        <a href="{{ route('jobs.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
            Back to Dashboard
        </a>
    </div>

    <div class="bg-white shadow-lg rounded-lg p-6">
        <form action="{{ route('jobs.store') }}" method="POST">
            @csrf
            
            @if($errors->any())
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm leading-5 font-medium text-red-800">
                                There were errors with your submission
                            </h3>
                            <div class="mt-2 text-sm text-red-700">
                                <ul class="list-disc pl-5 space-y-1">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="space-y-6">
                <div>
                    <label for="job_class" class="block text-sm font-medium text-gray-700">Job Class</label>
                    <select name="job_class" id="job_class" required
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 rounded-md">
                        <option value="">Select a job class</option>
                        @foreach(config('background-jobs.allowed_jobs') as $class => $methods)
                            <option value="{{ $class }}" {{ old('job_class') == $class ? 'selected' : '' }}>
                                {{ class_basename($class) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="method" class="block text-sm font-medium text-gray-700">Method</label>
                    <select name="method" id="method" required
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 rounded-md">
                        <option value="">Select a method</option>
                    </select>
                </div>

                <div>
                    <label for="parameters" class="block text-sm font-medium text-gray-700">Parameters (JSON)</label>
                    <textarea name="parameters" id="parameters" rows="4"
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                              placeholder='{"key": "value"}'
                    >{{ old('parameters') }}</textarea>
                    <p class="mt-1 text-sm text-gray-500">Enter parameters in JSON format</p>
                </div>

                <div>
                    <label for="priority" class="block text-sm font-medium text-gray-700">Priority</label>
                    <select name="priority" id="priority"
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 rounded-md">
                        @foreach(config('background-jobs.priorities.levels') as $level)
                            <option value="{{ $level }}" {{ old('priority', config('background-jobs.priorities.default')) == $level ? 'selected' : '' }}>
                                {{ $level }} - {{ $level == 1 ? 'Highest' : ($level == 5 ? 'Lowest' : 'Normal') }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="delay" class="block text-sm font-medium text-gray-700">Delay (seconds)</label>
                    <input type="number" name="delay" id="delay"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                           value="{{ old('delay', 0) }}" min="0">
                    <p class="mt-1 text-sm text-gray-500">Optional: Delay execution by specified seconds</p>
                </div>
            </div>

            <div class="mt-6">
                <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md">
                    Create Job
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const jobClassSelect = document.getElementById('job_class');
    const methodSelect = document.getElementById('method');
    const allowedJobs = @json(config('background-jobs.allowed_jobs'));

    jobClassSelect.addEventListener('change', function() {
        const selectedClass = this.value;
        methodSelect.innerHTML = '<option value="">Select a method</option>';
        
        if (selectedClass && allowedJobs[selectedClass]) {
            allowedJobs[selectedClass].forEach(method => {
                const option = document.createElement('option');
                option.value = method;
                option.textContent = method;
                if (method === '{{ old('method') }}') {
                    option.selected = true;
                }
                methodSelect.appendChild(option);
            });
        }
    });

    // Trigger change event if there's a previously selected value
    if (jobClassSelect.value) {
        jobClassSelect.dispatchEvent(new Event('change'));
    }
});
</script>
@endpush
@endsection 