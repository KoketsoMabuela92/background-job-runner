<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class BackgroundJob extends Model
{
    protected $fillable = [
        'job_class',
        'method',
        'parameters',
        'priority',
        'delay',
        'status',
        'attempts',
        'scheduled_at',
        'started_at',
        'completed_at',
        'error',
        'process_id',
    ];

    protected $casts = [
        'parameters' => 'array',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function scopePending($query)
    {
        return $query->where('status', 'pending')
                    ->where(function ($q) {
                        $q->whereNull('scheduled_at')
                          ->orWhere('scheduled_at', '<=', Carbon::now());
                    })
                    ->orderBy('priority')
                    ->orderBy('created_at');
    }

    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function markAsRunning()
    {
        $this->update([
            'status' => 'running',
            'started_at' => Carbon::now(),
        ]);
    }

    public function markAsCompleted()
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => Carbon::now(),
        ]);
    }

    public function markAsFailed($error)
    {
        $this->update([
            'status' => 'failed',
            'error' => $error,
            'completed_at' => Carbon::now(),
        ]);
    }

    public function incrementAttempts()
    {
        $this->increment('attempts');
    }

    public function canBeCancelled()
    {
        return in_array($this->status, ['running', 'pending']);
    }
} 