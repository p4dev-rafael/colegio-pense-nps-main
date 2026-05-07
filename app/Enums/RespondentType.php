<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum RespondentType: string implements HasColor, HasIcon, HasLabel
{
    case Student = 'student';
    case Guardian = 'guardian';
    case Anonymous = 'anonymous';

    public function getLabel(): string
    {
        return __('enums.respondent_type.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Student => 'info',
            self::Guardian => 'warning',
            self::Anonymous => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Student => 'heroicon-o-user',
            self::Guardian => 'heroicon-o-users',
            self::Anonymous => 'heroicon-o-eye-slash',
        };
    }

    public static function fromSegmentGroup(SegmentGroup $group): self
    {
        return match ($group) {
            SegmentGroup::Ei, SegmentGroup::Ef1 => self::Guardian,
            SegmentGroup::Ef2, SegmentGroup::Em => self::Student,
        };
    }
}
