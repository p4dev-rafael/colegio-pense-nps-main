<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('survey_section_id')
                ->constrained('survey_sections')
                ->cascadeOnDelete();
            $table->string('code', 30);
            $table->text('text');
            $table->string('type', 30);
            $table->boolean('is_required')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique('code');
            $table->index(['survey_section_id', 'sort_order']);
            $table->index('type');
            $table->index('is_active');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_questions');
    }
};
