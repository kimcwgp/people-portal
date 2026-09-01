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
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('team_head_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('deleted_at');
        });

        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->enum('shift_type', ['day', 'night']);
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('glip_url')->nullable();
            $table->boolean('status')->default(1);
            $table->boolean('online')->default(0);
            $table->unsignedBigInteger('team_id')->nullable();
            $table->unsignedBigInteger('shift_id')->nullable();
            $table->unsignedBigInteger('immediate_sup_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index('email');
            $table->index('status');
            $table->index('online');
            $table->index('immediate_sup_id');
            $table->index('deleted_at');

            // Foreign keys
            $table->foreign('team_id')->references('id')->on('teams')->nullOnDelete();
            $table->foreign('shift_id')->references('id')->on('shifts')->nullOnDelete();
            $table->foreign('immediate_sup_id')->references('id')->on('users')->nullOnDelete();
        });

        // Add foreign key for team_head_id after users table is created
        Schema::table('teams', function (Blueprint $table) {
            $table->foreign('team_head_id')->references('id')->on('users')->nullOnDelete();
        });

        // Password reset tokens
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // Sessions
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        // Login tokens for magic link authentication
        Schema::create('login_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('token')->unique();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
            
            // Auto-cleanup old tokens after 24 hours
            $table->index('created_at');
        });

        // Employees table - Employment specific information
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('employee_id', 50)->unique();
            
            // Employment dates
            $table->date('hire_date')->nullable();
            $table->date('regularization_date')->nullable();
            
            // Status
            $table->enum('employee_status', ['active', 'inactive', 'terminated', 'resigned'])
                  ->default('active');
            $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'intern', 'consultant'])
                  ->default('full_time');
            
            // Termination details (for soft delete context)
            $table->string('termination_reason')->nullable();
            $table->text('termination_notes')->nullable();
            $table->unsignedBigInteger('terminated_by')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('employee_id');
            $table->index('employee_status');
            $table->index('deleted_at');
            $table->foreign('terminated_by')->references('id')->on('users');
        });

        // Personal Information table
        Schema::create('personal_information', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            
            // Basic Information
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed'])->nullable();
            $table->string('spouse_name')->nullable();
            $table->integer('num_children')->default(0);
            
            // Contact Information
            $table->string('phone_number', 20)->nullable();
            $table->string('alternate_phone_number', 20)->nullable();
            $table->text('permanent_address')->nullable();
            $table->text('current_address')->nullable();
            
            // Emergency Contact
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_number', 20)->nullable();
            $table->string('emergency_contact_relationship', 50)->nullable();
            
            // Government IDs (Philippines specific)
            $table->string('tin', 50)->nullable(); // Tax ID
            $table->string('sss', 50)->nullable();
            $table->string('philhealth', 50)->nullable();
            $table->string('pagibig', 50)->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });

        // Job Information table (tracks position history)
        Schema::create('job_information', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            // Position Details
            $table->string('position_name', 150)->nullable();
            $table->string('position_level', 100)->nullable();
            $table->string('career_level', 100)->nullable();
            $table->string('career_band', 50)->nullable();
            $table->string('career_zone', 50)->nullable();
            
            // Reporting structure
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->boolean('is_manager')->default(false);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys
            $table->foreign('manager_id')->references('id')->on('users');
            
            // Indexes
            $table->index(['user_id']); // For finding current position
            $table->index('deleted_at');
        });

        // Salary Information table (sensitive data, separate for security)
        Schema::create('salary_information', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            $table->decimal('salary', 12, 2);
            $table->string('currency', 3)->default('PHP');
            $table->enum('pay_frequency', ['monthly', 'bi-weekly', 'weekly', 'daily'])
                  ->default('monthly');
            
            // Additional compensation
            $table->decimal('allowances', 10, 2)->nullable();
            $table->decimal('bonuses', 10, 2)->nullable();
            
            // For audit
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->boolean('is_archived')->default(false);
            
            $table->timestamps();
            $table->softDeletes(); // NEVER hard delete salary records
            
            // Foreign keys
            $table->foreign('approved_by')->references('id')->on('users');
            
            // Indexes
            $table->index(['user_id']); // For finding current salary
            $table->index('deleted_at');
        });

        // Employment History (audit trail)
        Schema::create('employment_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            
            $table->enum('change_type', [
                'hire', 'promotion', 'transfer', 'salary_change', 
                'status_change', 'termination', 'resignation', 'rehire',
                'role_change', 'team_change'
            ]);
            $table->string('from_value')->nullable();
            $table->string('to_value')->nullable();
            $table->text('description')->nullable();
            $table->date('effective_date');
            $table->unsignedBigInteger('created_by')->nullable();
            
            $table->timestamps();
            // No soft delete here - we want permanent audit trail
            
            $table->foreign('created_by')->references('id')->on('users');
            $table->index(['user_id', 'effective_date']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop in reverse order due to foreign key constraints
        Schema::dropIfExists('shifts');
        Schema::dropIfExists('employment_history');
        Schema::dropIfExists('salary_information');
        Schema::dropIfExists('job_information');
        Schema::dropIfExists('personal_information');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('login_tokens');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('teams');
    }
};