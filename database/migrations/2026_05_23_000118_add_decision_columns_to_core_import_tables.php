<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('core_import_records', function (Blueprint $table) {
            $table->string('validation_status')->nullable()->index();
            $table->string('suggested_action')->nullable()->index();
            $table->string('admin_decision')->nullable()->index();
            $table->text('decision_note')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->json('normalized_data')->nullable();
            $table->json('errors')->nullable();
            $table->json('warnings')->nullable();
            $table->json('conflicts')->nullable();
            $table->string('execution_status')->nullable()->index();
        });

        Schema::table('core_import_batches', function (Blueprint $table) {
            $table->string('decision_status')->nullable()->index();
            $table->unsignedInteger('decided_rows_count')->default(0);
            $table->unsignedInteger('pending_decision_rows_count')->default(0);
            $table->unsignedInteger('executable_rows_count')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('core_import_records', function (Blueprint $table) {
            $table->dropForeign(['decided_by']);
            $table->dropColumn([
                'validation_status',
                'suggested_action',
                'admin_decision',
                'decision_note',
                'decided_by',
                'decided_at',
                'normalized_data',
                'errors',
                'warnings',
                'conflicts',
                'execution_status',
            ]);
        });

        Schema::table('core_import_batches', function (Blueprint $table) {
            $table->dropColumn([
                'decision_status',
                'decided_rows_count',
                'pending_decision_rows_count',
                'executable_rows_count',
            ]);
        });
    }
};
