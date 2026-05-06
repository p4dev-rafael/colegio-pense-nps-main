<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum SegmentGroup: string implements HasColor, HasIcon, HasLabel
{
    case Ei = 'EI';
    case Ef1 = 'EF1';
    case Ef2 = 'EF2';
    case Em = 'EM';

    public function getLabel(): string
    {
        return __('enums.segment_group.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Ei => 'info',
            self::Ef1 => 'success',
            self::Ef2 => 'warning',
            self::Em => 'primary',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Ei => 'heroicon-o-heart',
            self::Ef1 => 'heroicon-o-academic-cap',
            self::Ef2 => 'heroicon-o-book-open',
            self::Em => 'heroicon-o-building-library',
        };
    }

    /** EI / EF1: guardian responds — subject_id on SegmentTeacher stays null typically. */
    public function expectsSubjectTeachers(): bool
    {
        return $this === self::Ef2 || $this === self::Em;
    }
}
