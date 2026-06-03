<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lecturers', function (Blueprint $table): void {
            if (! Schema::hasColumn('lecturers', 'national_id_number')) {
                $table->string('national_id_number')->nullable()->index()->after('lecturer_number');
            }

            if (! Schema::hasColumn('lecturers', 'nip')) {
                $table->string('nip')->nullable()->index()->after('national_id_number');
            }

            if (! Schema::hasColumn('lecturers', 'nidn')) {
                $table->string('nidn')->nullable()->index()->after('nip');
            }

            if (! Schema::hasColumn('lecturers', 'nidk')) {
                $table->string('nidk')->nullable()->index()->after('nidn');
            }

            if (! Schema::hasColumn('lecturers', 'nuptk')) {
                $table->string('nuptk')->nullable()->index()->after('nidk');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lecturers', function (Blueprint $table): void {
            foreach (['nuptk', 'nidk', 'nidn', 'nip', 'national_id_number'] as $column) {
                if (Schema::hasColumn('lecturers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
