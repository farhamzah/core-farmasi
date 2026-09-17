<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('career_alumni_registrations', function (Blueprint $table): void {
            $table->string('account_resolution')->nullable()->after('conflict_code');
            $table->text('review_note')->nullable()->after('account_resolution');
        });
    }

    public function down(): void
    {
        Schema::table('career_alumni_registrations', function (Blueprint $table): void {
            $table->dropColumn(['account_resolution', 'review_note']);
        });
    }
};
