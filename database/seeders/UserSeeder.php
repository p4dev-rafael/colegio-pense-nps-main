<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class UserSeeder extends Seeder
{
    public function run(): void
    {
        $north = Unit::query()->where('slug', 'unidade-norte')->firstOrFail();
        $south = Unit::query()->where('slug', 'unidade-sul')->firstOrFail();

        $admin = User::query()->firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('password'),
                'role' => UserRole::Admin,
                'is_active' => true,
            ],
        );
        $admin->units()->syncWithoutDetaching([$north->id, $south->id]);

        $operator = User::query()->firstOrCreate(
            ['email' => 'operador@colegiopense.edu.br'],
            [
                'name' => 'Operador',
                'password' => Hash::make('password'),
                'role' => UserRole::Operator,
                'is_active' => true,
            ],
        );
        $operator->units()->syncWithoutDetaching([$north->id]);
    }
}
