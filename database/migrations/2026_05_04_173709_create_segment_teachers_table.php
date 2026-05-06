<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('segment_teachers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('unit_id')->constrained('units')->cascadeOnDelete();
            $table->foreignUuid('segment_id')->constrained('segments')->cascadeOnDelete();
            $table->foreignUuid('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignUuid('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->timestamps();

            $table->index(['unit_id', 'segment_id']);
            $table->index('teacher_id');
        });

        /** Enforces uniqueness when subject_id is NULL (MySQL treats NULLs as distinct in plain UNIQUE indexes). */
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("
                CREATE UNIQUE INDEX segment_teachers_unit_segment_teacher_subject_unique
                ON segment_teachers (
                    unit_id,
                    segment_id,
                    teacher_id,
                    (COALESCE(subject_id, '00000000-0000-0000-0000-000000000000'))
                )
            ");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('DROP INDEX segment_teachers_unit_segment_teacher_subject_unique ON segment_teachers');
        }

        Schema::dropIfExists('segment_teachers');
    }
};
