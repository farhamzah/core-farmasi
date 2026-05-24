<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('core_import_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('core_import_batch_id')->constrained('core_import_batches')->cascadeOnDelete();
            $table->string('source_table');
            $table->string('source_id')->nullable();
            $table->string('source_identifier')->nullable();
            $table->string('target_table');
            $table->string('target_id')->nullable();
            $table->string('action');
            $table->json('payload_snapshot')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();

            $table->index(['source_table', 'source_identifier']);
            $table->index(['target_table', 'target_id']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_import_records');
    }
};
