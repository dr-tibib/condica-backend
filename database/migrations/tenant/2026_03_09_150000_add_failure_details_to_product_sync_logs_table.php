<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('product_sync_logs', 'failure_details')) {
            return;
        }

        try {
            Schema::table('product_sync_logs', function (Blueprint $table) {
                $table->json('failure_details')->nullable()->after('meta');
            });
        } catch (QueryException $e) {
            // MySQL: duplicate column name => SQLSTATE 42S21, error code 1060.
            if ($e->getCode() === 1060 || str_contains($e->getMessage(), 'Duplicate column name')) {
                return;
            }

            throw $e;
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('product_sync_logs', 'failure_details')) {
            return;
        }

        try {
            Schema::table('product_sync_logs', function (Blueprint $table) {
                $table->dropColumn('failure_details');
            });
        } catch (QueryException $e) {
            // Be tolerant in case the column already disappeared in a previous run.
            if (str_contains($e->getMessage(), "doesn't exist") || str_contains($e->getMessage(), 'Unknown column')) {
                return;
            }

            throw $e;
        }
    }
};
