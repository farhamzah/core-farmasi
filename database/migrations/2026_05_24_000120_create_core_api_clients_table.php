<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('core_api_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('core_application_id')->nullable()->constrained('core_applications')->nullOnDelete();
            $table->string('app_code')->index();
            $table->string('name');
            $table->string('client_id')->unique();
            $table->string('secret_hash')->nullable();
            $table->json('abilities')->nullable();
            $table->json('allowed_ips')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('last_rotated_at')->nullable();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('rotated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_api_clients');
    }
};
