<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        $this->replaceForeign('departments', 'faculty_id', 'faculties', onDelete: 'restrict');
        $this->replaceForeign('study_programs', 'faculty_id', 'faculties', onDelete: 'restrict');
        $this->replaceForeign('study_programs', 'department_id', 'departments', onDelete: 'restrict');
        $this->replaceForeign('students', 'study_program_id', 'study_programs', onDelete: 'restrict');
        $this->replaceForeign('lecturers', 'department_id', 'departments', onDelete: 'restrict');
        $this->replaceForeign('lecturers', 'study_program_id', 'study_programs', onDelete: 'restrict');
        $this->replaceForeign('employees', 'department_id', 'departments', onDelete: 'restrict');
        $this->replaceForeign('employees', 'study_program_id', 'study_programs', onDelete: 'restrict');
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        $this->replaceForeign('employees', 'study_program_id', 'study_programs', onDelete: 'null');
        $this->replaceForeign('employees', 'department_id', 'departments', onDelete: 'null');
        $this->replaceForeign('lecturers', 'study_program_id', 'study_programs', onDelete: 'null');
        $this->replaceForeign('lecturers', 'department_id', 'departments', onDelete: 'cascade');
        $this->replaceForeign('students', 'study_program_id', 'study_programs', onDelete: 'cascade');
        $this->replaceForeign('study_programs', 'department_id', 'departments', onDelete: 'null');
        $this->replaceForeign('study_programs', 'faculty_id', 'faculties', onDelete: 'null');
        $this->replaceForeign('departments', 'faculty_id', 'faculties', onDelete: 'null');
    }

    private function replaceForeign(string $tableName, string $columnName, string $referencesTable, string $onDelete): void
    {
        $this->dropForeignIfExists($tableName, $columnName);

        Schema::table($tableName, function (Blueprint $table) use ($columnName, $referencesTable, $onDelete): void {
            $foreign = $table->foreign($columnName)->references('id')->on($referencesTable);

            match ($onDelete) {
                'cascade' => $foreign->cascadeOnDelete(),
                'null' => $foreign->nullOnDelete(),
                default => $foreign->restrictOnDelete(),
            };
        });
    }

    private function dropForeignIfExists(string $tableName, string $columnName): void
    {
        $constraint = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $tableName)
            ->where('COLUMN_NAME', $columnName)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->value('CONSTRAINT_NAME');

        if (! $constraint) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($constraint): void {
            $table->dropForeign($constraint);
        });
    }
};
