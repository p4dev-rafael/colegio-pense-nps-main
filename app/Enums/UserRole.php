<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasColor, HasIcon, HasLabel
{
    case Admin = 'admin';
    case Operator = 'operator';

    public function getLabel(): string
    {
        return match ($this) {
            self::Admin => __('enums.user_role.admin'),
            self::Operator => __('enums.user_role.operator'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Admin => 'primary',
            self::Operator => 'info',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Admin => 'heroicon-o-shield-check',
            self::Operator => 'heroicon-o-user',
        };
    }
}
