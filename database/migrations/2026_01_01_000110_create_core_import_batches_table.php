<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('core_import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('source');
            $table->string('mode');
            $table->string('status');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->foreignId('operator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('options')->nullable();
            $table->json('summary')->nullable();
            $table->timestamps();

            $table->index(['source', 'mode', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_import_batches');
    }
};
