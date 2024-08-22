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
        'minimum_request_days',
        'balance_increment_period',
        'balance_increment_amount',
        'maximum_balance',
        'allow_carry_forward',
        'carry_forward_duration',
        'allow_advance',
        'advance_limit',
        'allow_accrual',
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
        'minimum_request_days' => 'integer',
        'balance_increment_amount' => 'integer',
        'maximum_balance' => 'integer',
        'allow_carry_forward' => 'boolean',
        'allow_advance' => 'boolean',
        'advance_limit' => 'integer',
        'allow_accrual' => 'boolean',
    ];

    public function entitlements(): HasMany
    {
        return $this->hasMany(LeaveEntitlement::class);
    }
}
