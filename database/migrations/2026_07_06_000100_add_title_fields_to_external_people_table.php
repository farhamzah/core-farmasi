<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_people', function (Blueprint $table): void {
            if (! Schema::hasColumn('external_people', 'front_title')) {
                $table->string('front_title', 100)->nullable()->after('name');
            }

            if (! Schema::hasColumn('external_people', 'back_title')) {
                $table->string('back_title', 100)->nullable()->after('front_title');
            }

            if (! Schema::hasColumn('external_people', 'title_updated_at')) {
                $table->timestamp('title_updated_at')->nullable()->after('back_title');
            }
        });
    }

    public function down(): void
    {
        Schema::table('external_people', function (Blueprint $table): void {
            if (Schema::hasColumn('external_people', 'title_updated_at')) {
                $table->dropColumn('title_updated_at');
            }

            if (Schema::hasColumn('external_people', 'back_title')) {
                $table->dropColumn('back_title');
            }

            if (Schema::hasColumn('external_people', 'front_title')) {
                $table->dropColumn('front_title');
            }
        });
    }
};
