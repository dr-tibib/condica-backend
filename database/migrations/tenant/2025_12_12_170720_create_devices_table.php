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
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('device_token');
            $table->string('device_name')->nullable();
            $table->enum('platform', ['ios', 'android']);
            $table->string('app_version')->nullable();
            $table->string('os_version')->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('last_active_at');
            $table->index(['user_id', 'platform']);
            $table->unique(['user_id', 'device_token']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
