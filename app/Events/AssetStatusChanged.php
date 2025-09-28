<?php

namespace App\Events;

use App\Models\Asset;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AssetStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $asset;
    public $oldStatus;
    public $newStatus;
    public $reason;
    public $timestamp;

    public function __construct(Asset $asset, string $oldStatus, string $newStatus, string $reason = null)
    {
        $this->asset = $asset;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
        $this->reason = $reason;
        $this->timestamp = now()->toISOString();
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('asset-updates'),
            new Channel("asset.{$this->asset->id}"),
            new Channel("site.{$this->asset->site_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'asset.status.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'asset_id' => $this->asset->id,
            'asset_name' => $this->asset->name,
            'site_id' => $this->asset->site_id,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'reason' => $this->reason,
            'timestamp' => $this->timestamp,
        ];
    }
}