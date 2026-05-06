<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum QuestionType: string implements HasColor, HasIcon, HasLabel
{
    case Scale1to5 = 'scale_1_to_5';
    case Scale0to10 = 'scale_0_to_10';
    case FreeText = 'free_text';

    public function getLabel(): string
    {
        return __('enums.question_type.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Scale1to5 => 'info',
            self::Scale0to10 => 'primary',
            self::FreeText => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Scale1to5 => 'heroicon-o-star',
            self::Scale0to10 => 'heroicon-o-chart-bar',
            self::FreeText => 'heroicon-o-pencil-square',
        };
    }

    public function allowsNsa(): bool
    {
        return $this === self::Scale1to5;
    }
}
