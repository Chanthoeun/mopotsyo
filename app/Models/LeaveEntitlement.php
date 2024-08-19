<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveEntitlement extends Model
{
    use HasFactory, SoftDeletes;

    protected $append = ['taken', 'remaining'];
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'start_date',
        'end_date',
        'balance',
        'is_active',
        'leave_type_id',
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
        'balance' => 'integer',
        'is_active' => 'boolean',
        'leave_type_id' => 'integer',
        'user_id' => 'integer',
    ];

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function leaveCarryForward(): HasOne
    {
        return $this->hasOne(LeaveCarryForward::class);
    }

    protected function taken(): Attribute
    {
        return Attribute::make(
            get: fn () => 0,
        );
    }

    protected function remaining(): Attribute
    {
        return Attribute::make(
            get: fn () => floatval($this->balance - $this->taken),
        );
    }
}