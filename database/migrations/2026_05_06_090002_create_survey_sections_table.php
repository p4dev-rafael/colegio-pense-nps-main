<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_sections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('survey_id')
                ->constrained('surveys')
                ->cascadeOnDelete();
            $table->string('title', 100);
            $table->text('description')->nullable();
            $table->string('type', 30);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['survey_id', 'sort_order']);
            $table->index('type');
            $table->index('is_active');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_sections');
    }
};
