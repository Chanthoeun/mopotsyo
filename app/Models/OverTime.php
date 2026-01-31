<?php

namespace App\Models;

use App\Settings\SettingWorkingHours;
use App\Traits\Approvable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OverTime extends Model
{
    use HasFactory, SoftDeletes, Approvable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'expiry_date',
        'reason',
        'unused',
        'user_id',
        'status',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'expiry_date' => 'date',
        'unused' => 'boolean',
        'status' => \App\Enums\Status::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function requestDates(): MorphMany
    {
        return $this->morphMany(RequestDate::class, 'requestdateable');
    }

    public function leaveRequests(): BelongsToMany
    {
        return $this->belongsToMany(LeaveRequest::class);
    }



    protected function hours(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->relationLoaded('requestDates')) {
                    return $this->requestDates->sum('hours');
                }
                return $this->requestDates()->sum('hours');
            },
        );
    }

    protected function days(): Attribute
    {
        return Attribute::make(
            get: function () {
                return floatval($this->hours / app(SettingWorkingHours::class)->day);
            },
        );
    }

    protected function requested(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->user?->full_name ?? '-',
        );
    }


}
