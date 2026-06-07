<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('account_requests', 'address')) {
                $table->text('address')->nullable()->after('phone');
            }

            if (! Schema::hasColumn('account_requests', 'birth_date')) {
                $table->date('birth_date')->nullable()->after('address');
            }

            if (! Schema::hasColumn('account_requests', 'gender')) {
                $table->string('gender')->nullable()->after('birth_date');
            }

            if (! Schema::hasColumn('account_requests', 'nip')) {
                $table->string('nip')->nullable()->index()->after('lecturer_number');
            }

            if (! Schema::hasColumn('account_requests', 'nidn')) {
                $table->string('nidn')->nullable()->index()->after('nip');
            }

            if (! Schema::hasColumn('account_requests', 'nidk')) {
                $table->string('nidk')->nullable()->index()->after('nidn');
            }

            if (! Schema::hasColumn('account_requests', 'nuptk')) {
                $table->string('nuptk')->nullable()->index()->after('nidk');
            }

            if (! Schema::hasColumn('account_requests', 'staff_type')) {
                $table->string('staff_type')->nullable()->after('employee_number');
            }

            if (! Schema::hasColumn('account_requests', 'position_title')) {
                $table->string('position_title')->nullable()->after('staff_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('account_requests', function (Blueprint $table) {
            foreach ([
                'position_title',
                'staff_type',
                'nuptk',
                'nidk',
                'nidn',
                'nip',
                'gender',
                'birth_date',
                'address',
            ] as $column) {
                if (Schema::hasColumn('account_requests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
