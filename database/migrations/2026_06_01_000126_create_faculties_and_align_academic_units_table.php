<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('faculties')) {
            Schema::create('faculties', function (Blueprint $table): void {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->boolean('active')->default(true)->index();
                $table->timestamps();
            });
        }

        Schema::table('departments', function (Blueprint $table): void {
            if (! Schema::hasColumn('departments', 'faculty_id')) {
                $table->foreignId('faculty_id')->nullable()->after('id')->constrained('faculties')->nullOnDelete();
            }
        });

        Schema::table('study_programs', function (Blueprint $table): void {
            if (! Schema::hasColumn('study_programs', 'faculty_id')) {
                $table->foreignId('faculty_id')->nullable()->after('id')->constrained('faculties')->nullOnDelete();
            }
        });

        if (DB::getDriverName() === 'mysql') {
            $this->makeStudyProgramDepartmentNullableOnMysql();
        }
    }

    public function down(): void
    {
        Schema::table('study_programs', function (Blueprint $table): void {
            if (Schema::hasColumn('study_programs', 'faculty_id')) {
                $table->dropConstrainedForeignId('faculty_id');
            }
        });

        Schema::table('departments', function (Blueprint $table): void {
            if (Schema::hasColumn('departments', 'faculty_id')) {
                $table->dropConstrainedForeignId('faculty_id');
            }
        });

        Schema::dropIfExists('faculties');
    }

    private function makeStudyProgramDepartmentNullableOnMysql(): void
    {
        $database = DB::getDatabaseName();
        $constraint = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', 'study_programs')
            ->where('COLUMN_NAME', 'department_id')
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->value('CONSTRAINT_NAME');

        if ($constraint) {
            DB::statement("ALTER TABLE study_programs DROP FOREIGN KEY `{$constraint}`");
        }

        DB::statement('ALTER TABLE study_programs MODIFY department_id BIGINT UNSIGNED NULL');

        DB::statement(
            'ALTER TABLE study_programs ADD CONSTRAINT study_programs_department_id_foreign FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL'
        );
    }
};
