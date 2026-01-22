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
        Schema::table('presence_events', function (Blueprint $table) {
            $table->enum('method', ['auto', 'manual', 'kiosk'])->default('manual')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('presence_events', function (Blueprint $table) {
            // Mapping back 'kiosk' to 'manual' would be ideal but for schema rollback:
            $table->enum('method', ['auto', 'manual'])->default('manual')->change();
        });
    }
};
