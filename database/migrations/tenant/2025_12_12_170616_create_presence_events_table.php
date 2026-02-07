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
        Schema::create('presence_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('workplace_id')->constrained()->onDelete('cascade');
            $table->enum('event_type', ['check_in', 'check_out']);
            $table->timestamp('event_time');
            $table->enum('method', ['auto', 'manual'])->default('manual');

            // Location data
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->integer('accuracy')->nullable()->comment('GPS accuracy in meters');

            // Device and app info
            $table->text('device_info')->nullable();
            $table->string('app_version')->nullable();
            $table->text('notes')->nullable();

            // Event pairing (link check-out to check-in)
            $table->foreignId('pair_event_id')->nullable()->constrained('presence_events')->onDelete('set null');

            $table->timestamps();

            // Indexes for performance
            $table->index('event_type');
            $table->index('event_time');
            $table->index(['employee_id', 'event_time']);
            $table->index(['workplace_id', 'event_time']);
            $table->index(['employee_id', 'event_type', 'event_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presence_events');
    }
};
