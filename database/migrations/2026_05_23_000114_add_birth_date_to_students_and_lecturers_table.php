<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'birth_date')) {
                $table->date('birth_date')->nullable();
            }
        });

        Schema::table('lecturers', function (Blueprint $table) {
            if (! Schema::hasColumn('lecturers', 'birth_date')) {
                $table->date('birth_date')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('lecturers', function (Blueprint $table) {
            if (Schema::hasColumn('lecturers', 'birth_date')) {
                $table->dropColumn('birth_date');
            }
        });

        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'birth_date')) {
                $table->dropColumn('birth_date');
            }
        });
    }
};
