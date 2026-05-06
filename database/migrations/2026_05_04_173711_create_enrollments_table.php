<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignUuid('unit_id')->constrained('units')->cascadeOnDelete();
            $table->foreignUuid('segment_id')->constrained('segments')->cascadeOnDelete();
            $table->string('registration_code', 30);
            $table->unsignedSmallInteger('year');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['student_id', 'unit_id', 'year']);
            $table->unique(['registration_code', 'unit_id', 'year']);
            $table->index(['unit_id', 'year']);
            $table->index('registration_code');
            $table->index('segment_id');
            $table->index('is_active');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
