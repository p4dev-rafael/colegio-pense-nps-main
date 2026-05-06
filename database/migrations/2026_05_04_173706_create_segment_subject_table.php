<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('segment_subject', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('segment_id')->constrained('segments')->cascadeOnDelete();
            $table->foreignUuid('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['segment_id', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('segment_subject');
    }
};
