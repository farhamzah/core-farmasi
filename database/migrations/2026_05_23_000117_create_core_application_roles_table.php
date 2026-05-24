<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('core_application_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('core_application_id')->nullable()->constrained('core_applications')->nullOnDelete();
            $table->string('app_code')->index();
            $table->string('role_slug');
            $table->string('role_name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['app_code', 'role_slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_application_roles');
    }
};
