<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lecturers', function (Blueprint $table): void {
            if (! Schema::hasColumn('lecturers', 'front_title')) {
                $table->string('front_title', 100)->nullable()->after('name');
            }

            if (! Schema::hasColumn('lecturers', 'back_title')) {
                $table->string('back_title', 100)->nullable()->after('front_title');
            }

            if (! Schema::hasColumn('lecturers', 'title_updated_at')) {
                $table->timestamp('title_updated_at')->nullable()->after('back_title');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lecturers', function (Blueprint $table): void {
            foreach (['title_updated_at', 'back_title', 'front_title'] as $column) {
                if (Schema::hasColumn('lecturers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
