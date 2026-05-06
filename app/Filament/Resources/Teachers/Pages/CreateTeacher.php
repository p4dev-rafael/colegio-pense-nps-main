<?php

declare(strict_types=1);

namespace App\Filament\Resources\Teachers\Pages;

use App\Filament\Resources\Teachers\TeacherResource;
use App\Models\Unit;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

final class CreateTeacher extends CreateRecord
{
    protected static string $resource = TeacherResource::class;

    protected function afterCreate(): void
    {
        $tenant = Filament::getTenant();
        if ($tenant instanceof Unit) {
            $this->record->units()->syncWithoutDetaching([$tenant->getKey()]);
        }
    }
}
