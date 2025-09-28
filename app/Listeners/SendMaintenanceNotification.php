<?php

namespace App\Listeners;

use App\Events\AssetStatusChanged;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification;

class SendMaintenanceNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct()
    {
        //
    }

    public function handle(AssetStatusChanged $event): void
    {
        $asset = $event->asset;
        $previousStatus = $event->previousStatus;
        $newStatus = $event->newStatus;

        // Define notification rules for different status changes
        $notifications = $this->getNotificationRules($asset, $previousStatus, $newStatus);

        foreach ($notifications as $notification) {
            $this->sendNotificationToUsers($notification, $asset, $previousStatus, $newStatus);
        }

        // Log the status change
        Log::info('Asset status changed', [
            'asset_id' => $asset->id,
            'asset_name' => $asset->name,
            'site_id' => $asset->site_id,
            'site_name' => $asset->site->name,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'changed_by' => $event->changedBy,
        ]);
    }

    private function getNotificationRules($asset, $previousStatus, $newStatus): array
    {
        $notifications = [];

        // Critical status changes - notify VEO Admin and Site Manager
        if (in_array($newStatus, ['offline', 'maintenance']) && $previousStatus === 'operational') {
            $notifications[] = [
                'recipients' => ['veo_admin', 'site_manager'],
                'priority' => 'critical',
                'title' => 'Asset Status Alert',
                'message' => "{$asset->name} at {$asset->site->name} changed from {$previousStatus} to {$newStatus}",
                'type' => 'warning'
            ];
        }

        // Maintenance notifications - notify Maintenance Staff
        if ($newStatus === 'maintenance') {
            $notifications[] = [
                'recipients' => ['maintenance_staff'],
                'priority' => 'high',
                'title' => 'Maintenance Required',
                'message' => "{$asset->name} requires maintenance attention",
                'type' => 'info'
            ];
        }

        // Recovery notifications - notify all relevant parties
        if ($newStatus === 'operational' && in_array($previousStatus, ['offline', 'maintenance'])) {
            $notifications[] = [
                'recipients' => ['veo_admin', 'site_manager', 'customer'],
                'priority' => 'medium',
                'title' => 'Asset Restored',
                'message' => "{$asset->name} is now operational",
                'type' => 'success'
            ];
        }

        // Emergency situations - notify everyone immediately
        if ($newStatus === 'emergency') {
            $notifications[] = [
                'recipients' => ['veo_admin', 'site_manager', 'maintenance_staff'],
                'priority' => 'critical',
                'title' => 'EMERGENCY: Asset Failure',
                'message' => "URGENT: {$asset->name} requires immediate attention",
                'type' => 'danger'
            ];
        }

        return $notifications;
    }

    private function sendNotificationToUsers($notification, $asset, $previousStatus, $newStatus): void
    {
        foreach ($notification['recipients'] as $role) {
            $users = User::where('role', $role)->get();

            foreach ($users as $user) {
                // Send Filament notification for admin users
                if (in_array($role, ['veo_admin', 'site_manager', 'maintenance_staff'])) {
                    Notification::make()
                        ->title($notification['title'])
                        ->body($notification['message'])
                        ->icon($this->getStatusIcon($newStatus))
                        ->color($notification['type'])
                        ->actions([
                            \Filament\Notifications\Actions\Action::make('view')
                                ->label('View Asset')
                                ->url(route('filament.admin.resources.assets.view', $asset))
                        ])
                        ->sendToDatabase($user);
                }

                // For customers, we could send email notifications
                if ($role === 'customer') {
                    // TODO: Send email notification to customer
                    Log::info('Customer notification would be sent', [
                        'customer_email' => $user->email,
                        'asset_name' => $asset->name,
                        'status_change' => "{$previousStatus} -> {$newStatus}"
                    ]);
                }
            }
        }
    }

    private function getStatusIcon($status): string
    {
        return match($status) {
            'operational' => 'heroicon-o-check-circle',
            'maintenance' => 'heroicon-o-wrench-screwdriver',
            'offline' => 'heroicon-o-x-circle',
            'emergency' => 'heroicon-o-exclamation-triangle',
            default => 'heroicon-o-cog-6-tooth'
        };
    }
}
