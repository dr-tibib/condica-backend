<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_sync_logs', function (Blueprint $table) {
            $table->json('failure_details')->nullable()->after('meta');
        });
    }

    public function down(): void
    {
        Schema::table('product_sync_logs', function (Blueprint $table) {
            $table->dropColumn('failure_details');
        });
    }
};
