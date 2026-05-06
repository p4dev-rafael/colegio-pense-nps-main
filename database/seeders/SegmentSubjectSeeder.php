<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Segment;
use App\Models\Subject;
use Illuminate\Database\Seeder;

/**
 * EF2 / EM segments receive N:N disciplines for survey Section 1.
 */
final class SegmentSubjectSeeder extends Seeder
{
    /** @var list<string> */
    private array $slugOrder = ['6o-ano', '7o-ano', '8o-ano', '9o-ano', '1a-serie', '2a-serie', '3a-serie'];

    public function run(): void
    {
        /** @var list<string> $subjectIds */
        $subjectIds = Subject::query()->orderBy('sort_order')->pluck('id')->all();

        if ($subjectIds === []) {
            return;
        }

        foreach ($this->slugOrder as $segmentSlug) {
            $segment = Segment::query()->where('slug', $segmentSlug)->first();
            if ($segment === null) {
                continue;
            }
            foreach ($subjectIds as $subjectId) {
                $segment->subjects()->syncWithoutDetaching([$subjectId]);
            }
        }
    }
}
