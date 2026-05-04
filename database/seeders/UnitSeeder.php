<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

final class UnitSeeder extends Seeder
{
    public function run(): void
    {
        Unit::query()->firstOrCreate(
            ['slug' => 'unidade-norte'],
            [
                'name' => 'Colégio Pense — Unidade Norte',
                'is_active' => true,
            ],
        );

        Unit::query()->firstOrCreate(
            ['slug' => 'unidade-sul'],
            [
                'name' => 'Colégio Pense — Unidade Sul',
                'is_active' => true,
            ],
        );
    }
}
