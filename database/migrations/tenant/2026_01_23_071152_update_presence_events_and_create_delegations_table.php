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
        // Update presence_events table
        Schema::table('presence_events', function (Blueprint $table) {
            $table->foreignId('workplace_id')->nullable()->change();
        });

        $connection = Schema::getConnection();
        if ($connection->getDriverName() === 'sqlite') {
            // SQLite: change to string to remove enum constraint
             Schema::table('presence_events', function (Blueprint $table) {
                $table->string('event_type')->change();
            });
        } else {
            // MySQL: update enum definition
            $connection->statement("ALTER TABLE presence_events MODIFY COLUMN event_type ENUM('check_in', 'check_out', 'delegation_start', 'delegation_end') NOT NULL");
        }

        // Create delegations table
        Schema::create('delegations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('place_id')->nullable()->comment('Google Place ID');
            $table->string('name');
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            $table->foreignId('start_event_id')->nullable()->constrained('presence_events')->onDelete('set null');
            $table->foreignId('end_event_id')->nullable()->constrained('presence_events')->onDelete('set null');

            $table->timestamps();

            $table->index('user_id');
            $table->index('place_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delegations');

        $connection = Schema::getConnection();
        // We won't strictly revert the enum changes as it might cause data loss if delegation events exist
        // and it's complex to revert safely without cleaning data.

        // Reverting workplace_id nullability is also risky if nulls exist.
    }
};
