<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('career_alumni_registrations', function (Blueprint $table): void {
            $table->id();
            $table->uuid('reference')->unique();
            $table->string('student_number')->index();
            $table->string('full_name');
            $table->string('claimed_program');
            $table->unsignedSmallInteger('graduation_year')->nullable();
            $table->string('personal_email')->index();
            $table->string('whatsapp');
            $table->string('password_hash')->nullable();
            $table->string('status')->default('pending')->index();
            $table->string('conflict_code')->nullable();
            $table->foreignId('matched_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['student_number', 'personal_email']);
        });

        Schema::create('career_alumni_grants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('career_scope')->default('farmasi');
            $table->string('eligibility_source');
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('approval_reference')->nullable();
            $table->timestamp('approved_at');
            $table->timestamps();

            $table->unique(['user_id', 'career_scope']);
        });

        Schema::create('career_identity_subjects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->uuid('subject')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('career_identity_subjects');
        Schema::dropIfExists('career_alumni_grants');
        Schema::dropIfExists('career_alumni_registrations');
    }
};
