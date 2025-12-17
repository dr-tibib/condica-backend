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
            $table->string('city')->nullable()->after('name');
            $table->string('county')->nullable()->after('city');
            $table->string('street_address')->nullable()->after('county');
            $table->string('country')->nullable()->after('street_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workplaces', function (Blueprint $table) {
            $table->dropColumn(['city', 'county', 'street_address', 'country']);
        });
    }
};
