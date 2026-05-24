<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            if (! Schema::hasColumn('students', 'phone')) {
                $table->string('phone')->nullable()->after('email');
            }

            if (! Schema::hasColumn('students', 'address')) {
                $table->text('address')->nullable()->after('phone');
            }
        });

        Schema::table('lecturers', function (Blueprint $table): void {
            if (! Schema::hasColumn('lecturers', 'address')) {
                $table->text('address')->nullable()->after('phone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lecturers', function (Blueprint $table): void {
            if (Schema::hasColumn('lecturers', 'address')) {
                $table->dropColumn('address');
            }
        });

        Schema::table('students', function (Blueprint $table): void {
            foreach (['address', 'phone'] as $column) {
                if (Schema::hasColumn('students', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
