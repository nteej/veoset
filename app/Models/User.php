<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
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

    public function sites()
    {
        return $this->belongsToMany(Site::class);
    }

    public function isVeoAdmin(): bool
    {
        return $this->hasRole('veo_admin');
    }

    public function isSiteManager(): bool
    {
        return $this->hasRole('site_manager');
    }

    public function isMaintenanceStaff(): bool
    {
        return $this->hasRole('maintenance_staff');
    }

    public function isCustomer(): bool
    {
        return $this->hasRole('customer');
    }

    public function canManageAssets(): bool
    {
        return $this->hasAnyRole(['veo_admin', 'site_manager']);
    }

    public function canExecuteTasks(): bool
    {
        return $this->hasAnyRole(['veo_admin', 'site_manager', 'maintenance_staff']);
    }

    public function canViewAssets(): bool
    {
        return $this->hasAnyRole(['veo_admin', 'site_manager', 'maintenance_staff', 'customer']);
    }

    // Filament interface methods
    public function canAccessPanel(Panel $panel): bool
    {
        // Check if user has valid roles for Filament access
        return $this->hasAnyRole(['veo_admin', 'site_manager', 'maintenance_staff', 'customer']);
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar_url ?? null;
    }
}
