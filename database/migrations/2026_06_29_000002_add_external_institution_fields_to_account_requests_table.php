<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_requests', function (Blueprint $table): void {
            if (! Schema::hasColumn('account_requests', 'institution_name')) {
                $table->string('institution_name')->nullable()->after('position_title');
            }

            if (! Schema::hasColumn('account_requests', 'institution_type')) {
                $table->string('institution_type')->nullable()->after('institution_name');
            }

            if (! Schema::hasColumn('account_requests', 'profession')) {
                $table->string('profession')->nullable()->after('institution_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('account_requests', function (Blueprint $table): void {
            foreach (['profession', 'institution_type', 'institution_name'] as $column) {
                if (Schema::hasColumn('account_requests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
