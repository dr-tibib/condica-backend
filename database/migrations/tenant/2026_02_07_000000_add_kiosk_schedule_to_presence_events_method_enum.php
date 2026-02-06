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
        $connection = Schema::getConnection();

        if ($connection->getDriverName() === 'sqlite') {
            // SQLite method column is already a string from previous migration,
            // so we don't strictly need to do anything, but to be explicit/consistent:
            Schema::table('presence_events', function (Blueprint $table) {
                $table->string('method')->change();
            });
        } else {
            // For MySQL/MariaDB, update the ENUM definition
            $connection->statement("ALTER TABLE presence_events MODIFY COLUMN method ENUM('auto', 'manual', 'kiosk', 'kiosk_schedule') DEFAULT 'manual'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = Schema::getConnection();

        if ($connection->getDriverName() === 'sqlite') {
            // No need to revert specifically for SQLite as string supports all previous values
        } else {
            // Revert back to previous ENUM definition
            // Note: This might fail if there are rows with 'kiosk_schedule'
            // We usually don't delete data in down() but we can't alter the column if data exists that violates the new constraint.
            // For now, we attempt to revert the definition.
            try {
                $connection->statement("ALTER TABLE presence_events MODIFY COLUMN method ENUM('auto', 'manual', 'kiosk') DEFAULT 'manual'");
            } catch (\Exception $e) {
                // If data exists, we can't revert easily without data loss or mapping.
                // We'll leave it as is or log a warning.
            }
        }
    }
};
