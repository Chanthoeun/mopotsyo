<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveCarryForward extends Model
{
    use HasFactory, SoftDeletes;

    protected $append = ['taken', 'remaining', 'is_active'];
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'start_date',
        'end_date',
        'balance',        
        'leave_entitlement_id',
        'user_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'leave_entitlement_id' => 'integer',
        'user_id' => 'integer',
    ];

    public function leaveEntitlement(): BelongsTo
    {
        return $this->belongsTo(LeaveEntitlement::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function requestDates(): HasMany
    {
        return $this->hasMany(RequestDate::class);
    }


    protected function balance(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => floatval($value),
        );
    }

    protected function taken(): Attribute
    {
        return Attribute::make(
            get: function (){
                $days = 0;
                foreach($this->requestDates as $requestDate){
                    $days += $requestDate->day;
                }

                return $days;
            },
        );
    }

    protected function remaining(): Attribute
    {
        return Attribute::make(
            get: fn () => floatval($this->balance - $this->taken),
        );
    }

    protected function isActive(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->end_date >= now() ? true : false,
        );
    }
}
