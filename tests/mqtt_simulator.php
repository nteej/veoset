<?php

/**
 * MQTT IoT Device Simulator
 *
 * This script simulates IoT devices sending data to our MQTT broker
 * for testing the integration
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

class IoTDeviceSimulator
{
    private $client;
    private $connectionSettings;
    private $deviceId;
    private $assetId;

    public function __construct(string $deviceId, int $assetId)
    {
        $this->deviceId = $deviceId;
        $this->assetId = $assetId;

        $this->connectionSettings = (new ConnectionSettings())
            ->setKeepAliveInterval(60)
            ->setConnectTimeout(3)
            ->setUseTls(false);
    }

    public function connect(): bool
    {
        try {
            $this->client = new MqttClient(
                'localhost',
                1883,
                $this->deviceId,
                MqttClient::MQTT_3_1_1
            );

            $this->client->connect($this->connectionSettings);
            echo "✅ Device {$this->deviceId} connected to MQTT broker\n";
            return true;
        } catch (Exception $e) {
            echo "❌ Failed to connect device {$this->deviceId}: " . $e->getMessage() . "\n";
            return false;
        }
    }

    public function registerDevice(): void
    {
        $registrationData = [
            'device_id' => $this->deviceId,
            'asset_id' => $this->assetId,
            'device_type' => 'sensor',
            'manufacturer' => 'VeoTech Industries',
            'model' => 'VT-SENSOR-2024',
            'firmware_version' => '1.2.3',
            'capabilities' => ['temperature', 'pressure', 'vibration', 'rpm'],
            'timestamp' => date('c'),
        ];

        $this->publish('veo/devices/register', $registrationData);
        echo "📝 Device {$this->deviceId} registered\n";
    }

    public function sendHeartbeat(): void
    {
        $heartbeatData = [
            'status' => 'online',
            'battery_level' => rand(75, 100),
            'signal_strength' => rand(-60, -30),
            'timestamp' => date('c'),
        ];

        $topic = "veo/devices/{$this->deviceId}/heartbeat";
        $this->publish($topic, $heartbeatData);
        echo "💓 Heartbeat sent from {$this->deviceId}\n";
    }

    public function sendAssetData(): void
    {
        $performanceData = [
            'temperature' => round(rand(200, 800) / 10, 1), // 20.0 - 80.0°C
            'pressure' => rand(80, 120), // 80-120 PSI
            'vibration' => round(rand(10, 50) / 10, 1), // 1.0 - 5.0 mm/s
            'rpm' => rand(1800, 3600), // 1800-3600 RPM
            'voltage' => round(rand(220, 240) / 10, 1), // 22.0 - 24.0V
            'current' => round(rand(80, 150) / 10, 1), // 8.0 - 15.0A
            'power' => rand(180, 360), // 180-360W
            'ambient_temperature' => round(rand(180, 350) / 10, 1), // 18.0 - 35.0°C
            'humidity' => rand(30, 70), // 30-70%
            'noise_level' => rand(45, 85), // 45-85 dB
            'timestamp' => date('c'),
        ];

        $topic = "veo/assets/{$this->assetId}/data";
        $this->publish($topic, $performanceData);
        echo "📊 Performance data sent for asset {$this->assetId}\n";
    }

    public function sendHealthData(): void
    {
        $healthScore = round(rand(75, 98) / 100, 2); // 0.75 - 0.98

        $healthData = [
            'health_score' => $healthScore * 100, // Convert to percentage
            'performance_score' => rand(85, 98),
            'diagnostics' => [
                'vibration_analysis' => 'normal',
                'temperature_trend' => 'stable',
                'power_efficiency' => rand(85, 95) . '%',
            ],
            'notes' => 'Automated health assessment',
            'timestamp' => date('c'),
        ];

        $topic = "veo/assets/{$this->assetId}/health";
        $this->publish($topic, $healthData);
        echo "🏥 Health data sent for asset {$this->assetId} (Score: " . ($healthScore * 100) . "%)\n";
    }

    public function sendDiagnosticData(): void
    {
        $hasErrors = rand(1, 10) <= 1; // 10% chance of errors
        $hasWarnings = rand(1, 5) <= 1; // 20% chance of warnings

        $diagnosticData = [
            'error_codes' => $hasErrors ? ['E001', 'E003'] : [],
            'warnings' => $hasWarnings ? ['W010: High temperature detected', 'W025: Vibration spike'] : [],
            'system_info' => [
                'cpu_usage' => rand(10, 80) . '%',
                'memory_usage' => rand(20, 70) . '%',
                'disk_space' => rand(40, 90) . '%',
            ],
            'sensor_status' => [
                'temperature_sensor' => 'active',
                'pressure_sensor' => 'active',
                'vibration_sensor' => 'active',
                'rpm_sensor' => 'active',
            ],
            'timestamp' => date('c'),
        ];

        $topic = "veo/assets/{$this->assetId}/diagnostic";
        $this->publish($topic, $diagnosticData);
        echo "🔧 Diagnostic data sent for asset {$this->assetId}\n";
    }

    public function sendStatusChange(): void
    {
        $statuses = ['operational', 'maintenance', 'offline'];
        $newStatus = $statuses[array_rand($statuses)];

        $statusData = [
            'status' => $newStatus,
            'reason' => 'Automated status update from IoT device',
            'timestamp' => date('c'),
        ];

        $topic = "veo/assets/{$this->assetId}/status";
        $this->publish($topic, $statusData);
        echo "🔄 Status changed to {$newStatus} for asset {$this->assetId}\n";
    }

    private function publish(string $topic, array $data): void
    {
        $message = json_encode($data);
        $this->client->publish($topic, $message, 0);
    }

    public function disconnect(): void
    {
        if ($this->client) {
            $this->client->disconnect();
            echo "❌ Device {$this->deviceId} disconnected\n";
        }
    }

    public function simulate(): void
    {
        if (!$this->connect()) {
            return;
        }

        // Register device
        $this->registerDevice();
        sleep(1);

        // Send initial heartbeat
        $this->sendHeartbeat();
        sleep(1);

        // Send data every few seconds
        for ($i = 0; $i < 10; $i++) {
            $this->sendAssetData();
            sleep(2);

            $this->sendHeartbeat();
            sleep(1);

            // Occasionally send health data
            if ($i % 3 == 0) {
                $this->sendHealthData();
                sleep(1);
            }

            // Occasionally send diagnostic data
            if ($i % 5 == 0) {
                $this->sendDiagnosticData();
                sleep(1);
            }

            // Rarely send status changes
            if ($i % 8 == 0) {
                $this->sendStatusChange();
                sleep(1);
            }
        }

        $this->disconnect();
    }
}

// Main execution
echo "🚀 Starting IoT Device Simulator\n";
echo "================================\n\n";

// Create multiple simulated devices
$devices = [
    new IoTDeviceSimulator('VT-PUMP-001', 1),
    new IoTDeviceSimulator('VT-MOTOR-002', 2),
    new IoTDeviceSimulator('VT-VALVE-003', 3),
];

// Check if MQTT broker is running
$mqttCheck = @fsockopen('localhost', 1883, $errno, $errstr, 2);
if (!$mqttCheck) {
    echo "❌ MQTT broker is not running on localhost:1883\n";
    echo "Please start Mosquitto broker: brew services start mosquitto\n";
    exit(1);
}
fclose($mqttCheck);

echo "✅ MQTT broker is running\n\n";

// Run simulations in parallel
$pids = [];
foreach ($devices as $device) {
    $pid = pcntl_fork();

    if ($pid == -1) {
        die('Could not fork process');
    } elseif ($pid) {
        // Parent process
        $pids[] = $pid;
    } else {
        // Child process
        $device->simulate();
        exit(0);
    }
}

// Wait for all child processes to complete
foreach ($pids as $pid) {
    pcntl_waitpid($pid, $status);
}

echo "\n✅ Simulation completed!\n";
echo "Check your Laravel application logs and database for the received data.\n";