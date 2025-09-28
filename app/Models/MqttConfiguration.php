<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;

class MqttConfiguration extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'host',
        'port',
        'use_tls',
        'tls_self_signed_allowed',
        'client_id_prefix',
        'description',
        'username',
        'password',
        'is_active',
        'ca_certificate',
        'client_certificate',
        'client_key',
        'certificate_password',
        'keep_alive_interval',
        'connect_timeout',
        'socket_timeout',
        'resend_timeout',
        'reconnect_delay',
        'publish_topics',
        'subscribe_topics',
        'topic_prefix',
        'topic_acl',
        'quality_of_service',
        'clean_session',
        'retain_messages',
        'max_reconnect_attempts',
        'last_will',
        'debug_logging',
        'last_connected_at',
        'last_error_at',
        'last_error_message',
        'connection_attempts',
        'connection_stats',
        'use_tls' => 'boolean',
        'tls_self_signed_allowed' => 'boolean',
        'is_active' => 'boolean',
        'clean_session' => 'boolean',
        'debug_logging' => 'boolean',
        'retain_messages' => 'boolean',
        'port' => 'integer',
        'keep_alive_interval' => 'integer',
        'connect_timeout' => 'integer',
        'socket_timeout' => 'integer',
        'resend_timeout' => 'integer',
        'reconnect_delay' => 'integer',
        'quality_of_service' => 'integer',
        'max_reconnect_attempts' => 'integer',
        'connection_attempts' => 'integer',
        'publish_topics' => 'array',
        'subscribe_topics' => 'array',
        'topic_acl' => 'array',
        'last_will' => 'array',
        'connection_stats' => 'array',
        'last_connected_at' => 'datetime',
        'last_error_at' => 'datetime',
    ];

    protected $casts = [
        'use_tls' => 'boolean',
        'tls_self_signed_allowed' => 'boolean',
        'is_active' => 'boolean',
        'clean_session' => 'boolean',
        'retain_messages' => 'boolean',
        'debug_logging' => 'boolean',
        'publish_topics' => 'array',
        'subscribe_topics' => 'array',
        'topic_acl' => 'array',
        'last_will' => 'array',
        'connection_stats' => 'array',
        'last_connected_at' => 'datetime',
        'last_error_at' => 'datetime',
    ];

    /**
     * Get the active MQTT configuration
     */
    public static function getActive()
    {
        return static::where('is_active', true)->first() ?? static::first();
    }

    /**
     * Create connection settings object from this configuration
     */
    public function getConnectionSettings(): ConnectionSettings
    {
        $settings = new ConnectionSettings();

        // Basic settings with explicit integer casting
        $settings->setKeepAliveInterval((int) $this->keep_alive_interval)
                ->setConnectTimeout((int) $this->connect_timeout)
                ->setSocketTimeout((int) $this->socket_timeout)
                ->setResendTimeout((int) $this->resend_timeout)
                ->setUseTls((bool) $this->use_tls)
                ->setTlsSelfSignedAllowed((bool) $this->tls_self_signed_allowed);
        
        // Authentication
        if ($this->username) {
            $settings->setUsername((string) $this->username);
        }
        
        if ($this->password) {
            $settings->setPassword((string) $this->password);
        }

        // Last Will and Testament
        if ($this->last_will && isset($this->last_will['topic'], $this->last_will['message'])) {
            $settings->setLastWillTopic((string) $this->last_will['topic'])
                    ->setLastWillMessage((string) $this->last_will['message'])
                    ->setLastWillQualityOfService((int) ($this->last_will['qos'] ?? 0));
        }

        return $settings;
    }

    /**
     * Generate a unique client ID based on prefix and optional suffix
     */
    public function generateClientId($suffix = null): string
    {
        $uniqueId = $suffix ?? uniqid();
        return $this->client_id_prefix . $uniqueId;
    }

    /**
     * Get a fully-formed topic with the configured prefix
     */
    public function getFullTopic(string $topic): string
    {
        return $this->topic_prefix . '/' . ltrim($topic, '/');
    }

    /**
     * Create an MQTT client instance with this configuration
     */
    public function createClient($clientIdSuffix = null): MqttClient
    {
        $clientId = $this->generateClientId($clientIdSuffix);
        
        return new MqttClient(
            $this->host,
            $this->port,
            $clientId,
            MqttClient::MQTT_3_1_1
        );
    }

    /**
     * Test connection to the MQTT broker
     */
    public function testConnection(): array
    {
        try {
            $client = $this->createClient('test');
            $connectionSettings = $this->getConnectionSettings();
            
            $startTime = microtime(true);
            $client->connect($connectionSettings, true);
            $endTime = microtime(true);
            
            $latency = round(($endTime - $startTime) * 1000, 2); // in milliseconds
            
            $client->disconnect();
            
            // Update connection stats
            $this->updateConnectionStats(true, $latency);
            
            return [
                'success' => true,
                'message' => 'Connection successful',
                'latency' => $latency,
                'broker' => "{$this->host}:{$this->port}",
            ];
        } catch (\Exception $e) {
            // Update connection stats
            $this->updateConnectionStats(false, null, $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'broker' => "{$this->host}:{$this->port}",
            ];
        }
    }

    /**
     * Update connection statistics
     */
    protected function updateConnectionStats(bool $success, ?float $latency = null, ?string $error = null): void
    {
        $now = now();
        $stats = $this->connection_stats ?? [];
        
        if ($success) {
            $this->last_connected_at = $now;
            $stats['last_success'] = $now->toISOString();
            $stats['last_latency'] = $latency;
            $stats['success_count'] = ($stats['success_count'] ?? 0) + 1;
        } else {
            $this->last_error_at = $now;
            $this->last_error_message = $error;
            $stats['last_error'] = $now->toISOString();
            $stats['last_error_message'] = $error;
            $stats['error_count'] = ($stats['error_count'] ?? 0) + 1;
        }
        
        $this->connection_attempts++;
        $stats['total_attempts'] = $this->connection_attempts;
        
        // Keep track of latency history (last 10)
        if ($latency) {
            $latencyHistory = $stats['latency_history'] ?? [];
            array_unshift($latencyHistory, [
                'timestamp' => $now->toISOString(),
                'latency' => $latency,
            ]);
            $stats['latency_history'] = array_slice($latencyHistory, 0, 10);
            
            // Calculate average latency
            $stats['avg_latency'] = array_sum(array_column($latencyHistory, 'latency')) / count($latencyHistory);
        }
        
        $this->connection_stats = $stats;
        $this->save();
    }

    /**
     * Get the default publish topics
     */
    public static function getDefaultPublishTopics(): array
    {
        return [
            'assets/{asset_id}/data' => 'Asset performance data',
            'assets/{asset_id}/status' => 'Asset status updates',
            'assets/{asset_id}/commands' => 'Commands for assets',
            'devices/{device_id}/heartbeat' => 'Device heartbeats',
        ];
    }

    /**
     * Get the default subscribe topics
     */
    public static function getDefaultSubscribeTopics(): array
    {
        return [
            'assets/+/data' => 'All asset data',
            'assets/+/status' => 'All asset status updates',
            'devices/+/heartbeat' => 'All device heartbeats',
            'devices/register' => 'Device registration',
        ];
    }

    /**
     * Parse topic pattern
     * Replace placeholders like {asset_id} with provided values
     */
    public function parseTopic(string $topicPattern, array $values = []): string
    {
        $topic = $topicPattern;
        
        foreach ($values as $key => $value) {
            $topic = str_replace("{{$key}}", $value, $topic);
        }
        
        return $this->getFullTopic($topic);
    }

    /**
     * Find specific topic from configured topics
     */
    public function findPublishTopic(string $key): ?string
    {
        $topics = $this->publish_topics ?? self::getDefaultPublishTopics();
        return $topics[$key] ?? null;
    }

    /**
     * Find specific topic from configured topics
     */
    public function findSubscribeTopic(string $key): ?string
    {
        $topics = $this->subscribe_topics ?? self::getDefaultSubscribeTopics();
        return $topics[$key] ?? null;
    }
}