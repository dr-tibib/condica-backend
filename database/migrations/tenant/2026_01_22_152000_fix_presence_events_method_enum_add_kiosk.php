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
            // SQLite doesn't support MODIFY COLUMN natively and ENUMs are Check constraints.
            // Changing to string removes the constraint, allowing 'kiosk'.
            Schema::table('presence_events', function (Blueprint $table) {
                $table->string('method')->change();
            });
        } else {
            // For MySQL/MariaDB, use direct SQL as requested to ensure ENUM options are updated.
            $connection->statement("ALTER TABLE presence_events MODIFY COLUMN method ENUM('auto', 'manual', 'kiosk') DEFAULT 'manual'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = Schema::getConnection();

        if ($connection->getDriverName() === 'sqlite') {
            Schema::table('presence_events', function (Blueprint $table) {
                // Revert back to enum with original options
                $table->enum('method', ['auto', 'manual'])->default('manual')->change();
            });
        } else {
            $connection->statement("ALTER TABLE presence_events MODIFY COLUMN method ENUM('auto', 'manual') DEFAULT 'manual'");
        }
    }
};
