<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('core_applications', function (Blueprint $table) {
            $table->id();
            $table->string('app_code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('base_url')->nullable();
            $table->string('admin_url')->nullable();
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_public_visible')->default(false)->index();
            $table->boolean('requires_login')->default(true);
            $table->boolean('is_sensitive')->default(false);
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_applications');
    }
};
