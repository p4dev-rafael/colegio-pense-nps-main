<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('survey_responses', function (Blueprint $table) {
            $table->dropForeign(['enrollment_id']);
            $table->dropForeign(['segment_id']);
        });

        Schema::table('survey_responses', function (Blueprint $table) {
            $table->dropUnique(['enrollment_id', 'survey_batch_id']);
        });

        Schema::table('survey_responses', function (Blueprint $table) {
            $table->foreignUuid('enrollment_id')->nullable()->change();
            $table->foreignUuid('segment_id')->nullable()->change();
        });

        Schema::table('survey_responses', function (Blueprint $table) {
            $table->foreign('enrollment_id')
                ->references('id')
                ->on('enrollments')
                ->cascadeOnDelete();
            $table->foreign('segment_id')
                ->references('id')
                ->on('segments')
                ->cascadeOnDelete();
        });

        Schema::table('survey_responses', function (Blueprint $table) {
            $table->unique(['enrollment_id', 'survey_batch_id']);
        });
    }

    public function down(): void
    {
        Schema::table('survey_responses', function (Blueprint $table) {
            $table->dropUnique(['enrollment_id', 'survey_batch_id']);
        });

        Schema::table('survey_responses', function (Blueprint $table) {
            $table->dropForeign(['enrollment_id']);
            $table->dropForeign(['segment_id']);
        });

        Schema::table('survey_responses', function (Blueprint $table) {
            $table->foreignUuid('enrollment_id')->nullable(false)->change();
            $table->foreignUuid('segment_id')->nullable(false)->change();
        });

        Schema::table('survey_responses', function (Blueprint $table) {
            $table->foreign('enrollment_id')
                ->references('id')
                ->on('enrollments')
                ->cascadeOnDelete();
            $table->foreign('segment_id')
                ->references('id')
                ->on('segments')
                ->cascadeOnDelete();
        });

        Schema::table('survey_responses', function (Blueprint $table) {
            $table->unique(['enrollment_id', 'survey_batch_id']);
        });
    }
};
