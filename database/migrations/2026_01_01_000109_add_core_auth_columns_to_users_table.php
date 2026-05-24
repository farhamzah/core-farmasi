<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'api_token')) {
                $table->string('api_token', 80)->unique()->nullable()->after('email');
            }

            if (! Schema::hasColumn('users', 'active')) {
                $table->boolean('active')->default(true)->after('api_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'active')) {
                $table->dropColumn('active');
            }

            if (Schema::hasColumn('users', 'api_token')) {
                $table->dropUnique('users_api_token_unique');
                $table->dropColumn('api_token');
            }
        });
    }
};
