<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Partner extends Model
{
    use HasFactory, SoftDeletes, HasTranslations;

    public $translatable = [
        'name',        
        'address',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'abbr',
        'address',
        'village_id',
        'commune_id',
        'district_id',
        'province_id',
        'map',
        'is_sale',
        'is_active',
        'partner_type_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'village_id' => 'integer',
        'commune_id' => 'integer',
        'district_id' => 'integer',
        'province_id' => 'integer',        
        'is_sale' => 'boolean',
        'is_active' => 'boolean',
        'partner_type_id' => 'integer',
    ];

    public function partnerType(): BelongsTo
    {
        return $this->belongsTo(PartnerType::class);
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function commune(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
