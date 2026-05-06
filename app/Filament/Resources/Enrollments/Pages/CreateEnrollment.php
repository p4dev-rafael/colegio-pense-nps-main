<?php

declare(strict_types=1);

namespace App\Filament\Resources\Enrollments\Pages;

use App\Filament\Resources\Enrollments\EnrollmentResource;
use App\Models\Unit;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

final class CreateEnrollment extends CreateRecord
{
    protected static string $resource = EnrollmentResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tenant = Filament::getTenant();
        if ($tenant instanceof Unit) {
            $data['unit_id'] = $tenant->getKey();
        }

        return $data;
    }
}
