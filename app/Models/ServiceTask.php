<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'assigned_to',
        'title',
        'description',
        'type',
        'priority',
        'status',
        'scheduled_date',
        'started_at',
        'completed_at',
        'estimated_duration_hours',
        'actual_duration_hours',
        'required_tools',
        'required_materials',
        'safety_requirements',
        'notes',
        'completion_notes',
        'requires_shutdown',
    ];

    protected $casts = [
        'scheduled_date' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'required_tools' => 'array',
        'required_materials' => 'array',
        'safety_requirements' => 'array',
        'requires_shutdown' => 'boolean',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeOverdue($query)
    {
        return $query->where('scheduled_date', '<', now())
                    ->whereIn('status', ['pending', 'in_progress']);
    }

    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRequiresShutdown($query)
    {
        return $query->where('requires_shutdown', true);
    }

    public function isOverdue(): bool
    {
        return $this->scheduled_date < now() &&
               in_array($this->status, ['pending', 'in_progress']);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCritical(): bool
    {
        return $this->priority === 'critical';
    }

    public function start(): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        $this->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        return true;
    }

    public function complete(?string $completionNotes = null): bool
    {
        if ($this->status !== 'in_progress') {
            return false;
        }

        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'completion_notes' => $completionNotes,
            'actual_duration_hours' => $this->started_at ?
                $this->started_at->diffInHours(now()) : null,
        ]);

        return true;
    }

    public function getDurationAttribute(): ?int
    {
        if ($this->started_at && $this->completed_at) {
            return $this->started_at->diffInHours($this->completed_at);
        }

        if ($this->started_at) {
            return $this->started_at->diffInHours(now());
        }

        return null;
    }
}
