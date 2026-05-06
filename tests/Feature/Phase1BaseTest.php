<?php

declare(strict_types=1);

use App\Models\Unit;
use App\Models\User;
use Database\Seeders\UnitSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(UnitSeeder::class);
    $this->seed(UserSeeder::class);
});

test('admin user is linked to both seeded units', function (): void {
    $admin = User::query()->where('email', 'test@example.com')->firstOrFail();
    expect($admin->units)->toHaveCount(2);
});

test('admin can access both tenants', function (): void {
    $admin = User::query()->where('email', 'test@example.com')->firstOrFail();
    $north = Unit::query()->where('slug', 'unidade-norte')->firstOrFail();
    $south = Unit::query()->where('slug', 'unidade-sul')->firstOrFail();

    expect($admin->canAccessTenant($north))->toBeTrue()
        ->and($admin->canAccessTenant($south))->toBeTrue();
});

test('operator cannot view any users per policy', function (): void {
    $operator = User::query()->where('email', 'operador@colegiopense.edu.br')->firstOrFail();

    expect($operator->can('viewAny', User::class))->toBeFalse();
});

test('admin can view any users per policy', function (): void {
    $admin = User::query()->where('email', 'test@example.com')->firstOrFail();

    expect($admin->can('viewAny', User::class))->toBeTrue();
});
