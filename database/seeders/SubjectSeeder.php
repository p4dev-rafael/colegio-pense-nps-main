<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;

final class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = [
            ['name' => 'Língua Portuguesa', 'slug' => 'lingua-portuguesa', 'sort_order' => 10],
            ['name' => 'Matemática', 'slug' => 'matematica', 'sort_order' => 20],
            ['name' => 'História', 'slug' => 'historia', 'sort_order' => 30],
            ['name' => 'Geografia', 'slug' => 'geografia', 'sort_order' => 40],
            ['name' => 'Ciências', 'slug' => 'ciencias', 'sort_order' => 50],
            ['name' => 'Inglês', 'slug' => 'ingles', 'sort_order' => 60],
            ['name' => 'Física', 'slug' => 'fisica', 'sort_order' => 70],
            ['name' => 'Química', 'slug' => 'quimica', 'sort_order' => 80],
            ['name' => 'Biologia', 'slug' => 'biologia', 'sort_order' => 90],
            ['name' => 'Filosofia', 'slug' => 'filosofia', 'sort_order' => 100],
        ];

        foreach ($definitions as $row) {
            Subject::query()->updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'name' => $row['name'],
                    'sort_order' => $row['sort_order'],
                    'is_active' => true,
                ],
            );
        }
    }
}
