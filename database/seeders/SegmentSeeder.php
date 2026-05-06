<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\SegmentGroup;
use App\Models\Segment;
use Illuminate\Database\Seeder;

final class SegmentSeeder extends Seeder
{
    public function run(): void
    {
        $segments = [
            ['name' => 'Maternal 1', 'slug' => 'maternal-1', 'group' => SegmentGroup::Ei->value, 'sort_order' => 1],
            ['name' => 'Maternal 2', 'slug' => 'maternal-2', 'group' => SegmentGroup::Ei->value, 'sort_order' => 2],
            ['name' => 'Jardim 1', 'slug' => 'jardim-1', 'group' => SegmentGroup::Ei->value, 'sort_order' => 3],
            ['name' => 'Jardim 2', 'slug' => 'jardim-2', 'group' => SegmentGroup::Ei->value, 'sort_order' => 4],
            ['name' => '1º ano', 'slug' => '1o-ano', 'group' => SegmentGroup::Ef1->value, 'sort_order' => 5],
            ['name' => '2º ano', 'slug' => '2o-ano', 'group' => SegmentGroup::Ef1->value, 'sort_order' => 6],
            ['name' => '3º ano', 'slug' => '3o-ano', 'group' => SegmentGroup::Ef1->value, 'sort_order' => 7],
            ['name' => '4º ano', 'slug' => '4o-ano', 'group' => SegmentGroup::Ef1->value, 'sort_order' => 8],
            ['name' => '5º ano', 'slug' => '5o-ano', 'group' => SegmentGroup::Ef1->value, 'sort_order' => 9],
            ['name' => '6º ano', 'slug' => '6o-ano', 'group' => SegmentGroup::Ef2->value, 'sort_order' => 10],
            ['name' => '7º ano', 'slug' => '7o-ano', 'group' => SegmentGroup::Ef2->value, 'sort_order' => 11],
            ['name' => '8º ano', 'slug' => '8o-ano', 'group' => SegmentGroup::Ef2->value, 'sort_order' => 12],
            ['name' => '9º ano', 'slug' => '9o-ano', 'group' => SegmentGroup::Ef2->value, 'sort_order' => 13],
            ['name' => '1ª série', 'slug' => '1a-serie', 'group' => SegmentGroup::Em->value, 'sort_order' => 14],
            ['name' => '2ª série', 'slug' => '2a-serie', 'group' => SegmentGroup::Em->value, 'sort_order' => 15],
            ['name' => '3ª série', 'slug' => '3a-serie', 'group' => SegmentGroup::Em->value, 'sort_order' => 16],
        ];

        foreach ($segments as $row) {
            Segment::query()->updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'name' => $row['name'],
                    'group' => $row['group'],
                    'sort_order' => $row['sort_order'],
                    'is_active' => true,
                ],
            );
        }
    }
}
