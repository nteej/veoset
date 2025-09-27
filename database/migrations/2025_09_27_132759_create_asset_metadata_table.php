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
        Schema::create('asset_metadata', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->onDelete('cascade');
            $table->json('specifications')->nullable(); // Technical specifications
            $table->json('maintenance_schedule')->nullable(); // Maintenance intervals and requirements
            $table->json('performance_metrics')->nullable(); // Performance KPIs and thresholds
            $table->json('safety_requirements')->nullable(); // Safety protocols and requirements
            $table->json('environmental_data')->nullable(); // Environmental operating conditions
            $table->decimal('power_rating', 10, 2)->nullable(); // Power rating in kW
            $table->string('voltage_level')->nullable(); // Operating voltage
            $table->integer('expected_lifespan_years')->nullable(); // Expected operational lifespan
            $table->decimal('efficiency_rating', 5, 2)->nullable(); // Efficiency percentage
            $table->text('operational_notes')->nullable(); // Additional operational notes
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_metadata');
    }
};
