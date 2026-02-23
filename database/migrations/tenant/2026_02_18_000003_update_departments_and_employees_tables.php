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
        Schema::table('departments', function (Blueprint $table) {
            if (!Schema::hasColumn('departments', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
            if (!Schema::hasColumn('departments', 'manager_id')) {
                $table->foreignId('manager_id')->nullable()->after('description')->constrained('employees')->nullOnDelete();
            }
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('avatar')->constrained('employee_roles')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('manager_id');
            $table->dropColumn('description');
        });
    }
};
