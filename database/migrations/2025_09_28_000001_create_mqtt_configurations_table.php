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
        Schema::create('mqtt_configurations', function (Blueprint $table) {
            $table->id();
            
            // Server Configuration
            $table->string('name')->default('Default Configuration');
            $table->string('host')->default('mqtt.smartforce.fi');
            $table->integer('port')->default(8883);
            $table->boolean('use_tls')->default(true);
            $table->boolean('tls_self_signed_allowed')->default(false);
            $table->string('client_id_prefix')->default('veoset-');
            $table->string('description')->nullable();
            
            // Authentication
            $table->string('username')->default('veosetuser');
            $table->string('password')->nullable();
            $table->boolean('is_active')->default(true);
            
            // TLS/SSL Configuration
            $table->string('ca_certificate')->nullable()->comment('CA certificate for TLS verification');
            $table->string('client_certificate')->nullable()->comment('Client certificate for TLS authentication');
            $table->string('client_key')->nullable()->comment('Client private key for TLS authentication');
            $table->string('certificate_password')->nullable()->comment('Password for client certificate if encrypted');
            
            // Timeouts and Intervals
            $table->integer('keep_alive_interval')->default(60);
            $table->integer('connect_timeout')->default(60);
            $table->integer('socket_timeout')->default(5);
            $table->integer('resend_timeout')->default(10);
            $table->integer('reconnect_delay')->default(10)->comment('Delay in seconds between reconnection attempts');
            
            // Topic Configuration
            $table->json('publish_topics')->nullable()->comment('Key-value pairs of topic patterns and their descriptions');
            $table->json('subscribe_topics')->nullable()->comment('Key-value pairs of topic patterns and their descriptions');
            $table->string('topic_prefix')->default('veo');
            $table->json('topic_acl')->nullable()->comment('Access control list for topics');
            
            // Advanced Settings
            $table->integer('quality_of_service')->default(0);
            $table->boolean('clean_session')->default(true);
            $table->boolean('retain_messages')->default(false);
            $table->integer('max_reconnect_attempts')->default(5);
            $table->json('last_will')->nullable()->comment('Last will and testament message configuration');
            $table->boolean('debug_logging')->default(false)->comment('Enable detailed debug logging');
            
            // Status Tracking
            $table->timestamp('last_connected_at')->nullable();
            $table->timestamp('last_error_at')->nullable();
            $table->string('last_error_message')->nullable();
            $table->integer('connection_attempts')->default(0);
            $table->json('connection_stats')->nullable()->comment('Connection statistics and metrics');
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mqtt_configurations');
    }
};