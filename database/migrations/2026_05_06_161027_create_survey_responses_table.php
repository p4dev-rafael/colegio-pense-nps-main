<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_responses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('survey_batch_id')->constrained('survey_batches')->cascadeOnDelete();
            $table->foreignUuid('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
            $table->foreignUuid('unit_id')->constrained('units')->cascadeOnDelete();
            $table->foreignUuid('segment_id')->constrained('segments')->cascadeOnDelete();
            $table->string('respondent_type', 20);
            $table->string('respondent_name', 100);
            $table->json('answers');
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['enrollment_id', 'survey_batch_id']);
            $table->index(['survey_batch_id', 'is_completed']);
            $table->index(['unit_id', 'segment_id', 'is_completed']);
            $table->index(['is_completed', 'completed_at']);
            $table->index('respondent_type');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_responses');
    }
};
