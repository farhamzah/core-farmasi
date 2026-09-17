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
        Schema::create('alumni', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('student_id')->nullable()->unique()->constrained('students')->nullOnDelete();
            $table->foreignId('study_program_id')->nullable()->constrained()->nullOnDelete();
            $table->string('student_number')->unique();
            $table->string('name');
            $table->string('personal_email')->nullable()->unique();
            $table->string('whatsapp', 50)->nullable();
            $table->string('program_name_snapshot')->nullable();
            $table->unsignedSmallInteger('entry_year')->nullable();
            $table->unsignedSmallInteger('graduation_year')->nullable()->index();
            $table->date('graduation_date')->nullable();
            $table->string('status')->default('verified')->index();
            $table->string('source')->default('manual')->index();
            $table->boolean('active')->default(true)->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alumni');
    }
};
