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
        Schema::table('delegation_places', function (Blueprint $table) {
            if (!Schema::hasColumn('delegation_places', 'metadata')) {
                $table->json('metadata')->nullable()->after('photo_reference');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delegation_places', function (Blueprint $table) {
            $table->dropColumn('metadata');
        });
    }
};
