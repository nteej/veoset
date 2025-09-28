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
        Schema::table('mqtt_devices', function (Blueprint $table) {
            $table->string('name')->after('device_id');
            $table->string('topic_prefix')->after('device_type');
            $table->string('location')->nullable()->after('firmware_version');
            $table->json('config')->nullable()->after('location');
            $table->date('installation_date')->nullable()->after('config');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mqtt_devices', function (Blueprint $table) {
            $table->dropColumn(['name', 'topic_prefix', 'location', 'config', 'installation_date']);
        });
    }
};
