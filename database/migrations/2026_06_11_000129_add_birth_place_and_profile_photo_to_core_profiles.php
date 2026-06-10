<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'profile_photo_path')) {
                $table->string('profile_photo_path')->nullable()->after('alternate_email');
            }
        });

        Schema::table('students', function (Blueprint $table): void {
            if (! Schema::hasColumn('students', 'birth_place')) {
                $table->string('birth_place')->nullable()->after('address');
            }
        });

        Schema::table('lecturers', function (Blueprint $table): void {
            if (! Schema::hasColumn('lecturers', 'birth_place')) {
                $table->string('birth_place')->nullable()->after('birth_date');
            }
        });

        Schema::table('employees', function (Blueprint $table): void {
            if (! Schema::hasColumn('employees', 'birth_place')) {
                $table->string('birth_place')->nullable()->after('birth_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            if (Schema::hasColumn('employees', 'birth_place')) {
                $table->dropColumn('birth_place');
            }
        });

        Schema::table('lecturers', function (Blueprint $table): void {
            if (Schema::hasColumn('lecturers', 'birth_place')) {
                $table->dropColumn('birth_place');
            }
        });

        Schema::table('students', function (Blueprint $table): void {
            if (Schema::hasColumn('students', 'birth_place')) {
                $table->dropColumn('birth_place');
            }
        });

        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'profile_photo_path')) {
                $table->dropColumn('profile_photo_path');
            }
        });
    }
};
