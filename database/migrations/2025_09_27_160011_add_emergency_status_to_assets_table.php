<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add emergency status to the enum
        DB::statement("ALTER TABLE assets MODIFY COLUMN status ENUM('operational', 'maintenance', 'offline', 'decommissioned', 'emergency') NOT NULL DEFAULT 'operational'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove emergency status from the enum (revert to original)
        DB::statement("ALTER TABLE assets MODIFY COLUMN status ENUM('operational', 'maintenance', 'offline', 'decommissioned') NOT NULL DEFAULT 'operational'");
    }
};
