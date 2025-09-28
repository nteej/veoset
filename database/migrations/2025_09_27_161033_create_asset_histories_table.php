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
        Schema::create('asset_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->onDelete('cascade');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->onDelete('set null');

            // Event tracking
            $table->enum('event_type', [
                'status_change',
                'maintenance_start',
                'maintenance_complete',
                'performance_reading',
                'alert_triggered',
                'diagnostic_scan',
                'shift_report'
            ]);
            $table->string('event_description');

            // Status tracking
            $table->enum('previous_status', ['operational', 'maintenance', 'offline', 'emergency'])->nullable();
            $table->enum('current_status', ['operational', 'maintenance', 'offline', 'emergency']);

            // Performance metrics (JSON for flexibility)
            $table->json('performance_data')->nullable();
            $table->json('diagnostic_data')->nullable();

            // Health calculations
            $table->decimal('health_score', 5, 2)->nullable(); // 0.00 to 100.00
            $table->enum('health_status', ['excellent', 'good', 'fair', 'poor', 'critical'])->nullable();

            // Environmental data
            $table->decimal('temperature', 8, 2)->nullable();
            $table->decimal('humidity', 5, 2)->nullable();
            $table->decimal('vibration_level', 8, 3)->nullable();
            $table->decimal('power_output', 10, 2)->nullable();
            $table->decimal('efficiency_percentage', 5, 2)->nullable();

            // Shift and technician info
            $table->enum('shift_type', ['day', 'night', 'emergency'])->nullable();
            $table->timestamp('shift_start')->nullable();
            $table->timestamp('shift_end')->nullable();

            // Alert and anomaly detection
            $table->boolean('anomaly_detected')->default(false);
            $table->text('anomaly_description')->nullable();
            $table->enum('severity_level', ['low', 'medium', 'high', 'critical'])->default('low');

            // Automated data source
            $table->enum('data_source', ['sensor', 'manual', 'system', 'maintenance', 'inspection'])->default('system');
            $table->boolean('is_automated')->default(true);

            // Additional metadata
            $table->json('metadata')->nullable(); // For extensible data
            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexes for performance
            $table->index(['asset_id', 'created_at']);
            $table->index(['event_type', 'created_at']);
            $table->index(['health_status', 'created_at']);
            $table->index(['shift_type', 'shift_start', 'shift_end']);
            $table->index(['anomaly_detected', 'severity_level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_histories');
    }
};
