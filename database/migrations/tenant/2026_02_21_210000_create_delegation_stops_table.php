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
        Schema::create('delegation_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delegation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('delegation_place_id')->nullable()->constrained()->nullOnDelete();
            $table->string('place_id')->nullable()->index(); // Google Place ID
            $table->string('name');
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->timestamps();
        });

        // Make existing fields in delegations nullable as we move to stops
        Schema::table('delegations', function (Blueprint $table) {
            $table->string('place_id')->nullable()->change();
            $table->string('name')->nullable()->change();
            $table->string('address')->nullable()->change();
            $table->decimal('latitude', 10, 8)->nullable()->change();
            $table->decimal('longitude', 11, 8)->nullable()->change();
            $table->foreignId('delegation_place_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delegation_stops');

        Schema::table('delegations', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
        });
    }
};
