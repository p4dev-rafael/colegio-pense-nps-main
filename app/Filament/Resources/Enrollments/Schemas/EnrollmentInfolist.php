<?php

declare(strict_types=1);

namespace App\Filament\Resources\Enrollments\Schemas;

use App\Models\Enrollment;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class EnrollmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('common.sections.general'))
                    ->schema([
                        TextEntry::make('student.name')
                            ->label(__('enrollments.fields.student_id')),
                        TextEntry::make('unit.name')
                            ->label(__('enrollments.fields.unit_id')),
                        TextEntry::make('segment.name')
                            ->label(__('enrollments.fields.segment_id')),
                        TextEntry::make('registration_code')
                            ->label(__('enrollments.fields.registration_code')),
                        TextEntry::make('year')
                            ->label(__('enrollments.fields.year')),
                        IconEntry::make('is_active')
                            ->label(__('enrollments.fields.is_active'))
                            ->boolean(),
                        TextEntry::make('created_at')
                            ->label(__('common.fields.created_at'))
                            ->dateTime()
                            ->placeholder('—'),
                        TextEntry::make('updated_at')
                            ->label(__('common.fields.updated_at'))
                            ->dateTime()
                            ->placeholder('—'),
                        TextEntry::make('deleted_at')
                            ->label(__('common.fields.deleted_at'))
                            ->dateTime()
                            ->placeholder('—')
                            ->visible(fn (Enrollment $record): bool => $record->trashed()),
                    ]),
            ]);
    }
}
