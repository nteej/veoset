<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'name',
        'description',
        'asset_type',
        'model',
        'manufacturer',
        'serial_number',
        'installation_date',
        'last_maintenance_date',
        'next_maintenance_date',
        'status',
        'mode',
        'is_active',
    ];

    protected $casts = [
        'installation_date' => 'date',
        'last_maintenance_date' => 'date',
        'next_maintenance_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function metadata(): HasOne
    {
        return $this->hasOne(AssetMetadata::class);
    }

    public function serviceTasks(): HasMany
    {
        return $this->hasMany(ServiceTask::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOperational($query)
    {
        return $query->where('status', 'operational');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('asset_type', $type);
    }

    public function scopeNeedsMaintenance($query)
    {
        return $query->where('next_maintenance_date', '<=', now());
    }

    public function isOperational(): bool
    {
        return $this->status === 'operational';
    }

    public function needsMaintenance(): bool
    {
        return $this->next_maintenance_date && $this->next_maintenance_date <= now();
    }
}
