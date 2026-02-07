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
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_paid')->default(true);
            $table->boolean('requires_document')->default(false);
            $table->boolean('affects_annual_quota')->default(false);
            $table->boolean('medical_code_required')->default(false);
            $table->timestamps();
        });

        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->integer('year');
            $table->float('total_entitlement')->default(21);
            $table->float('carried_over')->default(0);
            $table->float('taken')->default(0);
            $table->timestamps();

            $table->unique(['employee_id', 'year']);
        });

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('approver_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('leave_type_id')->constrained()->restrictOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->float('total_days');
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED', 'CANCELLED'])->default('PENDING');
            $table->string('medical_certificate_series')->nullable();
            $table->string('medical_certificate_number')->nullable();
            $table->string('medical_code')->nullable();
            $table->string('attachment_path')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('public_holidays', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->string('description');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('public_holidays');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('leave_types');
    }
};
