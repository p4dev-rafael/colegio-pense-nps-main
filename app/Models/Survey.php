<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Database\Factories\SurveyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $title
 * @property string|null $description
 * @property bool $is_active
 */
final class Survey extends Model
{
    /** @use HasFactory<SurveyFactory> */
    use HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<SurveySection, $this>
     */
    public function surveySections(): HasMany
    {
        return $this->hasMany(SurveySection::class);
    }

    /**
     * @param  Builder<Survey>  $query
     * @return Builder<Survey>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
