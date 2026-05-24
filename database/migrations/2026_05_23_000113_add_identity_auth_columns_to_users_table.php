<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'username')) {
                $table->string('username')->unique()->nullable();
            }

            if (! Schema::hasColumn('users', 'identity_type')) {
                $table->string('identity_type')->nullable()->index();
            }

            if (! Schema::hasColumn('users', 'identity_number')) {
                $table->string('identity_number')->nullable()->index();
            }

            if (! Schema::hasColumn('users', 'must_change_password')) {
                $table->boolean('must_change_password')->default(false);
            }

            if (! Schema::hasColumn('users', 'password_changed_at')) {
                $table->timestamp('password_changed_at')->nullable();
            }

            if (! Schema::hasColumn('users', 'last_password_reset_at')) {
                $table->timestamp('last_password_reset_at')->nullable();
            }

            if (! Schema::hasColumn('users', 'password_reset_by')) {
                $table->foreignId('password_reset_by')->nullable()->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'password_reset_by')) {
                $table->dropForeign(['password_reset_by']);
            }

            foreach ([
                'username',
                'identity_type',
                'identity_number',
                'must_change_password',
                'password_changed_at',
                'last_password_reset_at',
                'password_reset_by',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
