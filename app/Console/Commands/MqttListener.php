<?php

namespace App\Console\Commands;

use App\Services\MqttService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MqttListener extends Command
{
    protected $signature = 'mqtt:listen {--timeout=0 : Maximum execution time in seconds (0 for infinite)}';

    protected $description = 'Start MQTT listener to process IoT device messages';

    private $mqttService;
    private $shouldStop = false;

    public function __construct(MqttService $mqttService)
    {
        parent::__construct();
        $this->mqttService = $mqttService;
    }

    public function handle(): int
    {
        $timeout = (int) $this->option('timeout');
        $startTime = time();

        $this->info('Starting MQTT listener...');
        $this->info('Press Ctrl+C to stop gracefully');

        // Handle graceful shutdown
        pcntl_signal(SIGTERM, [$this, 'handleShutdown']);
        pcntl_signal(SIGINT, [$this, 'handleShutdown']);

        try {
            // Connect to MQTT broker
            if (!$this->mqttService->connect()) {
                $this->error('Failed to connect to MQTT broker');
                return Command::FAILURE;
            }

            $this->info('Connected to MQTT broker successfully');
            $this->mqttService->subscribeToTopics();
            $this->info('Subscribed to all VEO topics');

            // Start listening loop
            while (!$this->shouldStop) {
                pcntl_signal_dispatch();

                // Check timeout
                if ($timeout > 0 && (time() - $startTime) >= $timeout) {
                    $this->info('Timeout reached, stopping listener');
                    break;
                }

                try {
                    // Process MQTT messages (non-blocking)
                    $this->mqttService->client->loop(false, false, 1);

                    // Small delay to prevent high CPU usage
                    usleep(10000); // 10ms

                } catch (\Exception $e) {
                    $this->error('Error in MQTT loop: ' . $e->getMessage());
                    Log::error('MQTT Listener Error', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    // Try to reconnect after error
                    sleep(5);
                    if (!$this->mqttService->connect()) {
                        $this->error('Failed to reconnect to MQTT broker');
                        return Command::FAILURE;
                    }
                    $this->mqttService->subscribeToTopics();
                }
            }

        } catch (\Exception $e) {
            $this->error('MQTT Listener failed: ' . $e->getMessage());
            Log::error('MQTT Listener Fatal Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return Command::FAILURE;
        } finally {
            $this->mqttService->disconnect();
            $this->info('MQTT listener stopped');
        }

        return Command::SUCCESS;
    }

    public function handleShutdown(): void
    {
        $this->shouldStop = true;
        $this->info('Received shutdown signal, stopping gracefully...');
    }
}