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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('default_workplace_id')->nullable()->constrained('workplaces')->onDelete('set null');
            $table->string('employee_id')->nullable()->unique();
            $table->string('department')->nullable();
            $table->string('role')->nullable();

            // Indexes
            $table->index('default_workplace_id');
            $table->index('department');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['default_workplace_id']);
            $table->dropIndex(['default_workplace_id']);
            $table->dropIndex(['department']);
            $table->dropColumn(['default_workplace_id', 'employee_id', 'department', 'role']);
        });
    }
};
