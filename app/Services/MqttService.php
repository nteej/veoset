<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetHistory;
use App\Models\MqttDevice;
use App\Events\AssetDataUpdated;
use App\Events\AssetStatusChanged;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use Carbon\Carbon;

class MqttService
{
    public $client;
    private $connectionSettings;
    private $isConnected = false;

    // MQTT Configuration
    const MQTT_HOST = 'localhost';
    const MQTT_PORT = 1883;
    const MQTT_KEEPALIVE = 60;

    // Topic patterns for VEO system
    const TOPIC_ASSET_DATA = 'veo/assets/+/data';
    const TOPIC_ASSET_STATUS = 'veo/assets/+/status';
    const TOPIC_ASSET_HEALTH = 'veo/assets/+/health';
    const TOPIC_ASSET_DIAGNOSTIC = 'veo/assets/+/diagnostic';
    const TOPIC_DEVICE_REGISTER = 'veo/devices/register';
    const TOPIC_DEVICE_HEARTBEAT = 'veo/devices/+/heartbeat';

    public function __construct()
    {
        $this->connectionSettings = new ConnectionSettings();
        $this->connectionSettings
            ->setKeepAliveInterval(self::MQTT_KEEPALIVE)
            ->setConnectTimeout(3)
            ->setUseTls(false)
            ->setTlsSelfSignedAllowed(false);
    }

    /**
     * Connect to MQTT broker
     */
    public function connect(): bool
    {
        try {
            $this->client = new MqttClient(
                self::MQTT_HOST,
                self::MQTT_PORT,
                'veo-laravel-' . uniqid(),
                MqttClient::MQTT_3_1_1
            );

            $this->client->connect($this->connectionSettings, true);
            $this->isConnected = true;

            Log::info('MQTT Service: Connected to broker', [
                'host' => self::MQTT_HOST,
                'port' => self::MQTT_PORT,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('MQTT Service: Connection failed', [
                'error' => $e->getMessage(),
                'host' => self::MQTT_HOST,
                'port' => self::MQTT_PORT,
            ]);

            return false;
        }
    }

    /**
     * Subscribe to all VEO topics and process incoming messages
     */
    public function subscribeToTopics(): void
    {
        if (!$this->isConnected) {
            if (!$this->connect()) {
                return;
            }
        }

        $topics = [
            self::TOPIC_ASSET_DATA => [$this, 'handleAssetData'],
            self::TOPIC_ASSET_STATUS => [$this, 'handleAssetStatus'],
            self::TOPIC_ASSET_HEALTH => [$this, 'handleAssetHealth'],
            self::TOPIC_ASSET_DIAGNOSTIC => [$this, 'handleAssetDiagnostic'],
            self::TOPIC_DEVICE_REGISTER => [$this, 'handleDeviceRegistration'],
            self::TOPIC_DEVICE_HEARTBEAT => [$this, 'handleDeviceHeartbeat'],
        ];

        foreach ($topics as $topic => $callback) {
            $this->client->subscribe($topic, function (string $topic, string $message) use ($callback) {
                try {
                    Log::info('MQTT Message Received', [
                        'topic' => $topic,
                        'message_length' => strlen($message),
                    ]);

                    // Store in Redis for real-time processing
                    Redis::lpush('mqtt_messages', json_encode([
                        'topic' => $topic,
                        'message' => $message,
                        'timestamp' => now()->toISOString(),
                    ]));

                    // Process the message
                    call_user_func($callback, $topic, $message);

                } catch (\Exception $e) {
                    Log::error('MQTT Message Processing Error', [
                        'topic' => $topic,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }, 0);
        }

        Log::info('MQTT Service: Subscribed to all VEO topics');
    }

    /**
     * Handle asset performance/sensor data
     * Topic: veo/assets/{asset_id}/data
     */
    public function handleAssetData(string $topic, string $message): void
    {
        $assetId = $this->extractAssetIdFromTopic($topic);
        $data = json_decode($message, true);

        if (!$assetId || !$data) {
            Log::warning('MQTT: Invalid asset data message', [
                'topic' => $topic,
                'message' => $message,
            ]);
            return;
        }

        $asset = Asset::find($assetId);
        if (!$asset) {
            Log::warning('MQTT: Asset not found', ['asset_id' => $assetId]);
            return;
        }

        // Process sensor data
        $performanceData = [
            'temperature' => $data['temperature'] ?? null,
            'pressure' => $data['pressure'] ?? null,
            'vibration' => $data['vibration'] ?? null,
            'rpm' => $data['rpm'] ?? null,
            'voltage' => $data['voltage'] ?? null,
            'current' => $data['current'] ?? null,
            'power' => $data['power'] ?? null,
        ];

        $environmentalData = [
            'ambient_temperature' => $data['ambient_temperature'] ?? null,
            'humidity' => $data['humidity'] ?? null,
            'noise_level' => $data['noise_level'] ?? null,
        ];

        // Record in asset history
        AssetHistory::recordPerformanceReading($asset, $performanceData, $environmentalData);

        // Store in Redis for real-time dashboard
        $this->storeRealTimeData($assetId, 'performance', $performanceData);

        // Broadcast real-time update
        AssetDataUpdated::dispatch($assetId, $asset->name, $asset->site_id, 'performance', $performanceData);

        Log::info('MQTT: Asset data processed', [
            'asset_id' => $assetId,
            'asset_name' => $asset->name,
            'data_points' => count(array_filter($performanceData)),
        ]);
    }

    /**
     * Handle asset status changes from IoT devices
     * Topic: veo/assets/{asset_id}/status
     */
    public function handleAssetStatus(string $topic, string $message): void
    {
        $assetId = $this->extractAssetIdFromTopic($topic);
        $data = json_decode($message, true);

        if (!$assetId || !$data || !isset($data['status'])) {
            return;
        }

        $asset = Asset::find($assetId);
        if (!$asset) {
            Log::warning('MQTT: Asset not found for status update', ['asset_id' => $assetId]);
            return;
        }

        $newStatus = $data['status'];
        $reason = $data['reason'] ?? 'IoT device update';

        if (in_array($newStatus, ['operational', 'maintenance', 'emergency', 'offline'])) {
            $oldStatus = $asset->status;
            $asset->changeStatus($newStatus, 'iot_device', $reason);

            Log::info('MQTT: Asset status updated', [
                'asset_id' => $assetId,
                'asset_name' => $asset->name,
                'new_status' => $newStatus,
                'reason' => $reason,
            ]);

            // Broadcast status change for real-time updates
            AssetStatusChanged::dispatch($asset, $oldStatus, $newStatus, $reason);
        }
    }

    /**
     * Handle asset health score updates
     * Topic: veo/assets/{asset_id}/health
     */
    public function handleAssetHealth(string $topic, string $message): void
    {
        $assetId = $this->extractAssetIdFromTopic($topic);
        $data = json_decode($message, true);

        if (!$assetId || !$data || !isset($data['health_score'])) {
            return;
        }

        $asset = Asset::find($assetId);
        if (!$asset) {
            return;
        }

        $healthScore = floatval($data['health_score']);
        $diagnosticData = $data['diagnostics'] ?? [];

        // Create health record
        AssetHistory::create([
            'asset_id' => $assetId,
            'event_type' => 'iot_health_update',
            'health_score' => $healthScore,
            'performance_score' => $data['performance_score'] ?? null,
            'diagnostic_data' => $diagnosticData,
            'notes' => $data['notes'] ?? 'IoT health update',
            'recorded_by' => 'iot_device',
        ]);

        // Store for real-time updates
        $this->storeRealTimeData($assetId, 'health', ['health_score' => $healthScore]);

        // Broadcast health update
        AssetDataUpdated::dispatch($assetId, $asset->name, $asset->site_id, 'health', ['health_score' => $healthScore]);

        // Check for critical health and trigger alerts
        if ($healthScore < 30) {
            $this->triggerCriticalHealthAlert($asset, $healthScore);
        }

        Log::info('MQTT: Asset health updated', [
            'asset_id' => $assetId,
            'health_score' => $healthScore,
        ]);
    }

    /**
     * Handle diagnostic data from IoT devices
     * Topic: veo/assets/{asset_id}/diagnostic
     */
    public function handleAssetDiagnostic(string $topic, string $message): void
    {
        $assetId = $this->extractAssetIdFromTopic($topic);
        $data = json_decode($message, true);

        if (!$assetId || !$data) {
            return;
        }

        $asset = Asset::find($assetId);
        if (!$asset) {
            return;
        }

        // Process diagnostic data
        $diagnosticData = [
            'error_codes' => $data['error_codes'] ?? [],
            'warnings' => $data['warnings'] ?? [],
            'system_info' => $data['system_info'] ?? [],
            'sensor_status' => $data['sensor_status'] ?? [],
        ];

        $anomalyDetected = !empty($data['error_codes']) ||
                          (!empty($data['warnings']) && count($data['warnings']) > 2);

        AssetHistory::create([
            'asset_id' => $assetId,
            'event_type' => 'diagnostic_scan',
            'diagnostic_data' => $diagnosticData,
            'anomaly_detected' => $anomalyDetected,
            'anomaly_description' => $anomalyDetected ?
                'IoT diagnostic detected issues: ' . implode(', ', $data['error_codes'] ?? []) : null,
            'severity_level' => $this->determineSeverityLevel($data),
            'notes' => $data['notes'] ?? 'IoT diagnostic scan',
            'recorded_by' => 'iot_device',
        ]);

        Log::info('MQTT: Asset diagnostic processed', [
            'asset_id' => $assetId,
            'anomaly_detected' => $anomalyDetected,
            'error_count' => count($data['error_codes'] ?? []),
        ]);
    }

    /**
     * Handle device registration
     * Topic: veo/devices/register
     */
    public function handleDeviceRegistration(string $topic, string $message): void
    {
        $data = json_decode($message, true);

        if (!$data || !isset($data['device_id'], $data['asset_id'])) {
            return;
        }

        $device = MqttDevice::updateOrCreate(
            ['device_id' => $data['device_id']],
            [
                'asset_id' => $data['asset_id'],
                'device_type' => $data['device_type'] ?? 'sensor',
                'manufacturer' => $data['manufacturer'] ?? null,
                'model' => $data['model'] ?? null,
                'firmware_version' => $data['firmware_version'] ?? null,
                'capabilities' => $data['capabilities'] ?? [],
                'is_active' => true,
                'last_seen' => now(),
                'registration_data' => $data,
            ]
        );

        Log::info('MQTT: Device registered', [
            'device_id' => $data['device_id'],
            'asset_id' => $data['asset_id'],
            'device_type' => $data['device_type'] ?? 'sensor',
        ]);
    }

    /**
     * Handle device heartbeat
     * Topic: veo/devices/{device_id}/heartbeat
     */
    public function handleDeviceHeartbeat(string $topic, string $message): void
    {
        $deviceId = $this->extractDeviceIdFromTopic($topic);
        $data = json_decode($message, true);

        if (!$deviceId) {
            return;
        }

        $device = MqttDevice::where('device_id', $deviceId)->first();
        if ($device) {
            $device->update([
                'last_seen' => now(),
                'status' => $data['status'] ?? 'online',
                'battery_level' => $data['battery_level'] ?? null,
                'signal_strength' => $data['signal_strength'] ?? null,
            ]);
        }
    }

    /**
     * Publish message to MQTT topic
     */
    public function publish(string $topic, array $data, int $qos = 0): bool
    {
        if (!$this->isConnected) {
            if (!$this->connect()) {
                return false;
            }
        }

        try {
            $message = json_encode($data);
            $this->client->publish($topic, $message, $qos);

            Log::info('MQTT: Message published', [
                'topic' => $topic,
                'data_size' => strlen($message),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('MQTT: Publish failed', [
                'topic' => $topic,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send command to IoT device
     */
    public function sendCommand(int $assetId, string $command, array $parameters = []): bool
    {
        $topic = "veo/assets/{$assetId}/commands";
        $data = [
            'command' => $command,
            'parameters' => $parameters,
            'timestamp' => now()->toISOString(),
            'source' => 'veo_system',
        ];

        return $this->publish($topic, $data);
    }

    /**
     * Request asset data update
     */
    public function requestAssetData(int $assetId): bool
    {
        return $this->sendCommand($assetId, 'request_data', [
            'include' => ['performance', 'health', 'diagnostics']
        ]);
    }

    /**
     * Start MQTT listener daemon
     */
    public function startListening(): void
    {
        if (!$this->isConnected) {
            if (!$this->connect()) {
                throw new \Exception('Cannot connect to MQTT broker');
            }
        }

        $this->subscribeToTopics();

        Log::info('MQTT Service: Starting message loop');

        // Keep listening for messages
        $this->client->loop(true);
    }

    /**
     * Disconnect from MQTT broker
     */
    public function disconnect(): void
    {
        if ($this->isConnected && $this->client) {
            $this->client->disconnect();
            $this->isConnected = false;
            Log::info('MQTT Service: Disconnected from broker');
        }
    }

    // Helper methods

    private function extractAssetIdFromTopic(string $topic): ?int
    {
        // Extract asset ID from topic like "veo/assets/123/data"
        if (preg_match('/veo\/assets\/(\d+)\//', $topic, $matches)) {
            return intval($matches[1]);
        }
        return null;
    }

    private function extractDeviceIdFromTopic(string $topic): ?string
    {
        // Extract device ID from topic like "veo/devices/device123/heartbeat"
        if (preg_match('/veo\/devices\/([^\/]+)\//', $topic, $matches)) {
            return $matches[1];
        }
        return null;
    }

    private function storeRealTimeData(int $assetId, string $type, array $data): void
    {
        $key = "realtime:asset:{$assetId}:{$type}";
        $payload = array_merge($data, ['timestamp' => now()->toISOString()]);

        Redis::setex($key, 300, json_encode($payload)); // Store for 5 minutes
        Redis::lpush("realtime:updates", json_encode([
            'asset_id' => $assetId,
            'type' => $type,
            'data' => $payload,
        ]));
        Redis::ltrim("realtime:updates", 0, 99); // Keep last 100 updates
    }

    private function broadcastStatusChange(Asset $asset, string $newStatus): void
    {
        // Broadcast to WebSocket clients for real-time updates
        Redis::publish('asset_status_changed', json_encode([
            'asset_id' => $asset->id,
            'asset_name' => $asset->name,
            'site_id' => $asset->site_id,
            'old_status' => $asset->getOriginal('status'),
            'new_status' => $newStatus,
            'timestamp' => now()->toISOString(),
        ]));
    }

    private function triggerCriticalHealthAlert(Asset $asset, float $healthScore): void
    {
        // Store alert in Redis for immediate processing
        Redis::lpush('critical_health_alerts', json_encode([
            'asset_id' => $asset->id,
            'asset_name' => $asset->name,
            'site_id' => $asset->site_id,
            'health_score' => $healthScore,
            'timestamp' => now()->toISOString(),
            'source' => 'iot_device',
        ]));

        Log::critical('MQTT: Critical health alert triggered', [
            'asset_id' => $asset->id,
            'health_score' => $healthScore,
        ]);
    }

    private function determineSeverityLevel(array $data): string
    {
        $errorCount = count($data['error_codes'] ?? []);
        $warningCount = count($data['warnings'] ?? []);

        if ($errorCount > 0) {
            return 'critical';
        } elseif ($warningCount > 3) {
            return 'warning';
        } elseif ($warningCount > 0) {
            return 'info';
        }

        return 'normal';
    }
}