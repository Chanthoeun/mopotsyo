<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class LeaveType extends Model
{
    use HasFactory, SoftDeletes, HasTranslations;

    public $translatable = [
        'name'
    ];
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'abbr',
        'color',
        'male',
        'female',
        'balance',
        'maximum_balance',
        'option',
        'rules',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'male' => 'boolean',
        'female' => 'boolean',
        'balance' => 'integer',
        'maximum_balance' => 'integer',
        'option' => 'array',
        'rules' => 'array',
    ];

    public function entitlements(): HasMany
    {
        return $this->hasMany(LeaveEntitlement::class);
    }
}
