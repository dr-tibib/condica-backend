<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('license_plate');
            $table->timestamps();
        });

        Schema::create('delegation_places', function (Blueprint $table) {
            $table->id();
            $table->string('google_place_id')->unique();
            $table->string('name');
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->text('photo_reference')->nullable();
            $table->timestamps();
        });

        Schema::table('delegations', function (Blueprint $table) {
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->onDelete('set null');
            $table->foreignId('delegation_place_id')->nullable()->constrained('delegation_places')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('delegations', function (Blueprint $table) {
            $table->dropForeign(['vehicle_id']);
            $table->dropForeign(['delegation_place_id']);
            $table->dropColumn(['vehicle_id', 'delegation_place_id']);
        });

        Schema::dropIfExists('delegation_places');
        Schema::dropIfExists('vehicles');
    }
};
