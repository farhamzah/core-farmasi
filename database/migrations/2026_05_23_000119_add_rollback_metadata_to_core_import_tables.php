<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('lecturers', function (Blueprint $table) {
            if (! Schema::hasColumn('lecturers', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('core_import_records', function (Blueprint $table) {
            $table->string('target_type')->nullable()->index();
            $table->string('executed_action')->nullable()->index();
            $table->foreignId('executed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('executed_at')->nullable();
            $table->json('previous_snapshot')->nullable();
            $table->string('rollback_status')->nullable()->index();
            $table->text('rollback_note')->nullable();
            $table->foreignId('rolled_back_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rolled_back_at')->nullable();
            $table->json('rollback_result')->nullable();
            $table->unsignedBigInteger('created_user_id')->nullable()->index();
            $table->unsignedBigInteger('linked_user_id')->nullable()->index();
        });

        Schema::table('core_import_batches', function (Blueprint $table) {
            $table->string('rollback_status')->nullable()->index();
            $table->unsignedInteger('rolled_back_rows_count')->default(0);
            $table->unsignedInteger('rollback_failed_rows_count')->default(0);
            $table->unsignedInteger('rollback_skipped_rows_count')->default(0);
            $table->foreignId('rolled_back_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rolled_back_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('core_import_batches', function (Blueprint $table) {
            $table->dropForeign(['rolled_back_by']);
            $table->dropColumn([
                'rollback_status',
                'rolled_back_rows_count',
                'rollback_failed_rows_count',
                'rollback_skipped_rows_count',
                'rolled_back_by',
                'rolled_back_at',
            ]);
        });

        Schema::table('core_import_records', function (Blueprint $table) {
            $table->dropForeign(['executed_by']);
            $table->dropForeign(['rolled_back_by']);
            $table->dropColumn([
                'target_type',
                'executed_action',
                'executed_by',
                'executed_at',
                'previous_snapshot',
                'rollback_status',
                'rollback_note',
                'rolled_back_by',
                'rolled_back_at',
                'rollback_result',
                'created_user_id',
                'linked_user_id',
            ]);
        });

        Schema::table('lecturers', function (Blueprint $table) {
            if (Schema::hasColumn('lecturers', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
