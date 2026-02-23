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
        Schema::table('workplaces', function (Blueprint $table) {
            $table->time('default_shift_start')->default('08:00')->after('timezone');
            $table->time('default_shift_end')->default('17:00')->after('default_shift_start');
            $table->time('late_start_threshold')->default('16:00')->after('default_shift_end');
            $table->json('settings')->nullable()->after('late_start_threshold');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workplaces', function (Blueprint $table) {
            $table->dropColumn(['default_shift_start', 'default_shift_end', 'late_start_threshold', 'settings']);
        });
    }
};
