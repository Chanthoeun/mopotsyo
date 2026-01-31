<?php

namespace App\Models;

use App\Enums\ApprovalStatuEnum;
use App\Settings\SettingWorkingHours;
use App\Traits\Approvable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveRequest extends Model
{
    use HasFactory, SoftDeletes, Approvable;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'leave_type_id',
        'from_date',
        'to_date',
        'reason',
        'attachment',
        'status',
        'is_completed',
        'user_id'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'leave_type_id' => 'integer',
        'from_date' => 'date',
        'to_date' => 'date',
        'is_completed' => 'boolean',
        'status' => \App\Enums\Status::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function requestDates(): MorphMany
    {
        return $this->morphMany(RequestDate::class, 'requestdateable');
    }



    public function leaverequestable(): MorphTo
    {
        return $this->morphTo();
    }

    public function overTimes(): BelongsToMany
    {
        return $this->belongsToMany(OverTime::class, 'leave_request_over_time', 'leave_request_id', 'over_time_id');
    }

    protected function requested(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->user?->full_name ?? '-',
        );
    }

    protected function days(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->relationLoaded('requestDates')) {
                    return floatval($this->requestDates->sum('hours') / app(SettingWorkingHours::class)->day);
                }
                return floatval($this->requestDates()->sum('hours') / app(SettingWorkingHours::class)->day);
            },
        );
    }



    protected function backDate(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->from_date < $this->created_at ? true : false,
        );
    }


}
