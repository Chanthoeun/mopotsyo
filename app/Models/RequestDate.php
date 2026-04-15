<?php

namespace App\Models;

use App\Settings\SettingWorkingHours;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class RequestDate extends Model
{
    use HasFactory;

    public $with = ['requestdateable'];

    protected static array $cfTakenHoursCache = [];

    protected static function booted()
    {
        static::saving(function (RequestDate $requestDate) {
            if ($requestDate->requestdateable_type !== LeaveRequest::class) {
                return;
            }

            $leaveRequest = $requestDate->requestdateable ?: LeaveRequest::find($requestDate->requestdateable_id);
            if (!$leaveRequest) {
                return;
            }

            $leaveType = $leaveRequest->leaveType;
            if (!$leaveType) {
                return;
            }

            $allowCarryForward = $leaveType->option['allow_carry_forward'] ?? false;

            if (!$allowCarryForward) {
                $name = $leaveType->getTranslation('name', 'en');
                $allowCarryForward = (stripos($name, 'Annual') !== false);
            }

            if ($allowCarryForward) {
                $carryForward = LeaveCarryForward::where('user_id', $leaveRequest->user_id)
                    ->whereDate('start_date', '<=', $requestDate->date)
                    ->whereDate('end_date', '>=', $requestDate->date)
                    ->first();

                if ($carryForward) {
                    $dayLength = app(SettingWorkingHours::class)->day ?: 8;

                    $cfId = $carryForward->id;
                    if (!isset(static::$cfTakenHoursCache[$cfId])) {
                        $query = $carryForward->requestDates()
                            ->whereHasMorph('requestdateable', [LeaveRequest::class], function ($q) {
                                $q->whereIn('status', [
                                    \App\Enums\Status::APPROVED,
                                    \App\Enums\Status::PENDING,
                                    \App\Enums\Status::WAITING,
                                ]);
                            });

                        static::$cfTakenHoursCache[$cfId] = $query->sum('hours');
                        
                        if ($requestDate->exists && $requestDate->getOriginal('leave_carry_forward_id') == $cfId) {
                            static::$cfTakenHoursCache[$cfId] -= $requestDate->getOriginal('hours');
                        }
                    }

                    $takenDays = static::$cfTakenHoursCache[$cfId] / $dayLength;
                    $currentDays = $requestDate->hours / $dayLength;

                    if (round($carryForward->balance - $takenDays, 4) >= round($currentDays, 4)) {
                        $requestDate->leave_carry_forward_id = $cfId;
                        static::$cfTakenHoursCache[$cfId] += $requestDate->hours;
                    } else {
                        $requestDate->leave_carry_forward_id = null;
                    }
                } else {
                    $requestDate->leave_carry_forward_id = null;
                }
            } else {
                $requestDate->leave_carry_forward_id = null;
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'date',
        'start_time',
        'end_time',
        'hours',
        'leave_carry_forward_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'date' => 'date',
    ];

    public function requestdateable(): MorphTo
    {
        return $this->morphTo();
    }

    public function leaveCarryForward(): BelongsTo
    {
        return $this->belongsTo(LeaveCarryForward::class);
    }



    protected function day(): Attribute
    {
        return Attribute::make(
            get: function () {
                $dayLength = app(SettingWorkingHours::class)->day ?: 8;
                return floatval($this->hours / $dayLength);
            },
        );
    }
}
