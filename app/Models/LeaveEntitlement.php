<?php

namespace App\Models;

use App\Settings\SettingWorkingHours;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use RingleSoft\LaravelProcessApproval\Enums\ApprovalActionEnum;

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

    public function leaveRequests(): MorphMany
    {
        return $this->morphMany(LeaveRequest::class, 'leaverequestable');
    }

    protected function taken(): Attribute
    {
        return Attribute::make(
            get: function () {               
                $leaveRequests = $this->user->leaveRequests()->with('requestDates')->where('leave_type_id', $this->leave_type_id)->whereHas('requestDates', function($q){
                    $q->whereBetween('date', [$this->start_date, $this->end_date]);
                })->whereHas('approvalStatus', static function ($q) {
                    return $q->where('status', ApprovalActionEnum::APPROVED->value);
                })->get();

                $taken = 0;
                foreach ($leaveRequests as $leaveRequest) {
                    $taken += floatval($leaveRequest->requestDates->sum('hours') / app(SettingWorkingHours::class)->day);
                }
                return $taken;
            } 
        );
    }

    protected function remaining(): Attribute
    {
        return Attribute::make(
            get: fn () => floatval($this->balance - $this->taken),
        );
    }

    protected function accrued(): Attribute
    {
        return Attribute::make(
            get: function(){
                if($this->leaveType->option->allow_accrual){
                    return floatval(calculateAccrud($this->balance, $this->start_date, now()) - $this->taken);
                }
                return 0;
            },
        );
    }
}
