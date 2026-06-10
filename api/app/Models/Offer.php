<?php

namespace App\Models;

use App\Enums\Sector;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'brand_id', 'title', 'description', 'sector', 'discount_code',
    'discount_description', 'starts_at', 'expires_at', 'is_active',
    'max_redemptions', 'redemption_count',
])]
class Offer extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sector' => Sector::class,
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
            'max_redemptions' => 'integer',
            'redemption_count' => 'integer',
        ];
    }

    /** @return BelongsTo<Brand, $this> */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /** @return HasMany<Redemption, $this> */
    public function redemptions(): HasMany
    {
        return $this->hasMany(Redemption::class);
    }

    /**
     * The offers a member of a given sector is allowed to browse: active and
     * targeted at their sector. Window/capacity/duplicate checks happen at
     * redemption time (RedemptionService), not here, so an expired-but-active
     * offer still appears in the list and surfaces a clear error on redeem.
     *
     * @param  Builder<Offer>  $query
     * @return Builder<Offer>
     */
    public function scopeForSector(Builder $query, Sector $sector): Builder
    {
        return $query->where('sector', $sector)->where('is_active', true);
    }
}
