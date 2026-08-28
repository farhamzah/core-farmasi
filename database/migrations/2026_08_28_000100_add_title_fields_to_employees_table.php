<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('front_title')->nullable()->after('name');
            $table->string('back_title')->nullable()->after('front_title');
            $table->timestamp('title_updated_at')->nullable()->after('back_title');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'front_title',
                'back_title',
                'title_updated_at',
            ]);
        });
    }
};
