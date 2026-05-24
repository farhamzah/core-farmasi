<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leadership_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('position_type')->index();
            $table->string('position_title')->nullable();
            $table->string('unit_type')->index();
            $table->unsignedBigInteger('unit_id')->nullable()->index();
            $table->string('person_type')->index();
            $table->unsignedBigInteger('person_id')->index();
            $table->string('title_prefix')->nullable();
            $table->string('title_suffix')->nullable();
            $table->string('official_name_snapshot')->nullable();
            $table->string('decree_number')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['unit_type', 'unit_id']);
            $table->index(['person_type', 'person_id']);
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leadership_assignments');
    }
};
