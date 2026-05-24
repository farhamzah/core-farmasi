<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('core_api_request_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('core_api_client_id')->nullable()->constrained('core_api_clients')->nullOnDelete();
            $table->string('app_code')->nullable()->index();
            $table->string('client_id')->nullable()->index();
            $table->string('method', 16);
            $table->string('path');
            $table->string('route_name')->nullable();
            $table->unsignedSmallInteger('status_code')->nullable()->index();
            $table->string('ability')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('request_id')->nullable()->index();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->boolean('is_success')->default(false);
            $table->string('error_code')->nullable();
            $table->string('error_message', 512)->nullable();
            $table->timestamp('created_at')->nullable()->index();

            $table->index('core_api_client_id');
            $table->index(['app_code', 'created_at']);
            $table->index(['client_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_api_request_logs');
    }
};
