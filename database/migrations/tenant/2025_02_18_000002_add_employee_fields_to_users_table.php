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
            $table->text('address')->nullable();
            $table->string('id_document_type')->nullable(); // 'CI', 'BI', 'Pasaport'
            $table->string('id_document_number')->nullable();
            $table->string('personal_numeric_code')->unique()->nullable();
            $table->string('workplace_enter_code')->nullable();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn([
                'address',
                'id_document_type',
                'id_document_number',
                'personal_numeric_code',
                'workplace_enter_code',
                'department_id',
            ]);
        });
    }
};
