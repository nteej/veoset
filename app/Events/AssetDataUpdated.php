<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AssetDataUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $assetId;
    public $assetName;
    public $siteId;
    public $dataType;
    public $data;
    public $timestamp;

    public function __construct(int $assetId, string $assetName, int $siteId, string $dataType, array $data)
    {
        $this->assetId = $assetId;
        $this->assetName = $assetName;
        $this->siteId = $siteId;
        $this->dataType = $dataType;
        $this->data = $data;
        $this->timestamp = now()->toISOString();
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('asset-updates'),
            new Channel("asset.{$this->assetId}"),
            new Channel("site.{$this->siteId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'asset.data.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'asset_id' => $this->assetId,
            'asset_name' => $this->assetName,
            'site_id' => $this->siteId,
            'data_type' => $this->dataType,
            'data' => $this->data,
            'timestamp' => $this->timestamp,
        ];
    }
}