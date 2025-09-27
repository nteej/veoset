<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function serviceTasks()
    {
        return $this->hasMany(ServiceTask::class, 'assigned_to');
    }

    public function isVeoAdmin(): bool
    {
        return $this->role === 'veo_admin';
    }

    public function isSiteManager(): bool
    {
        return $this->role === 'site_manager';
    }

    public function isMaintenanceStaff(): bool
    {
        return $this->role === 'maintenance_staff';
    }

    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }

    public function canManageAssets(): bool
    {
        return in_array($this->role, ['veo_admin', 'site_manager']);
    }

    public function canExecuteTasks(): bool
    {
        return in_array($this->role, ['veo_admin', 'site_manager', 'maintenance_staff']);
    }

    public function canViewAssets(): bool
    {
        return in_array($this->role, ['veo_admin', 'site_manager', 'maintenance_staff', 'customer']);
    }
}
