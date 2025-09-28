<?php

namespace App\Filament\Auth;

use App\Models\User;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class CustomUserProvider implements FilamentUser
{
    protected User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // Check if user has valid roles for Filament access
        return $this->user->hasAnyRole(['veo_admin', 'site_manager', 'maintenance_staff', 'customer']);
    }

    public function getFilamentName(): string
    {
        return $this->user->name;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->user->avatar_url;
    }

    public function __call($method, $arguments)
    {
        return $this->user->$method(...$arguments);
    }

    public function __get($property)
    {
        return $this->user->$property;
    }
}