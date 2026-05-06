<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum SectionType: string implements HasColor, HasIcon, HasLabel
{
    case Teachers = 'teachers';
    case Coordination = 'coordination';
    case Secretariat = 'secretariat';
    case PhysicalStructure = 'physical_structure';
    case Cafeteria = 'cafeteria';
    case SocialMedia = 'social_media';
    case Chaplaincy = 'chaplaincy';
    case Institutional = 'institutional';
    case NpsFinal = 'nps_final';

    public function getLabel(): string
    {
        return __('enums.section_type.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Teachers => 'info',
            self::Coordination => 'success',
            self::Secretariat => 'warning',
            self::PhysicalStructure => 'primary',
            self::Cafeteria => 'amber',
            self::SocialMedia => 'violet',
            self::Chaplaincy => 'rose',
            self::Institutional => 'gray',
            self::NpsFinal => 'danger',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Teachers => 'heroicon-o-user-group',
            self::Coordination => 'heroicon-o-clipboard-document-check',
            self::Secretariat => 'heroicon-o-document-text',
            self::PhysicalStructure => 'heroicon-o-building-office',
            self::Cafeteria => 'heroicon-o-cake',
            self::SocialMedia => 'heroicon-o-megaphone',
            self::Chaplaincy => 'heroicon-o-heart',
            self::Institutional => 'heroicon-o-academic-cap',
            self::NpsFinal => 'heroicon-o-star',
        };
    }
}
