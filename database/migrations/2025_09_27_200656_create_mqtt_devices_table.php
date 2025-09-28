<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mqtt_devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_id')->unique();
            $table->foreignId('asset_id')->constrained()->onDelete('cascade');
            $table->string('device_type')->default('sensor'); // sensor, controller, gateway, etc.
            $table->string('manufacturer')->nullable();
            $table->string('model')->nullable();
            $table->string('firmware_version')->nullable();
            $table->json('capabilities')->nullable(); // What the device can do
            $table->enum('status', ['online', 'offline', 'error', 'maintenance'])->default('offline');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_seen')->nullable();
            $table->integer('battery_level')->nullable(); // 0-100%
            $table->integer('signal_strength')->nullable(); // dBm or percentage
            $table->json('configuration')->nullable(); // Device-specific config
            $table->json('registration_data')->nullable(); // Original registration payload
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['asset_id', 'device_type']);
            $table->index(['status', 'is_active']);
            $table->index('last_seen');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mqtt_devices');
    }
};
