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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();

            // ID Document
            $table->string('id_document_type')->nullable(); // 'CI', 'BI', 'Pasaport'
            $table->string('id_document_number')->nullable();
            $table->string('personal_numeric_code')->unique()->nullable();

            // Work related
            $table->string('workplace_enter_code')->unique()->nullable();
            $table->string('avatar')->nullable();

            // Relationships
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('workplace_id')->nullable()->constrained('workplaces')->nullOnDelete();

            $table->timestamps();

            // Indexes
            $table->index('workplace_enter_code');
            $table->index('department_id');
            $table->index('workplace_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
