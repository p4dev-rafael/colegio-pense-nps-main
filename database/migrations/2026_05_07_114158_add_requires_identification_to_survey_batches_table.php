<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('survey_batches', function (Blueprint $table) {
            $table->boolean('requires_identification')->default(true)->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('survey_batches', function (Blueprint $table) {
            $table->dropColumn('requires_identification');
        });
    }
};
