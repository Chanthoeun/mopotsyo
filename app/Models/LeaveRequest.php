<?php

namespace App\Models;

use App\Enums\ApprovalStatuEnum;
use App\Settings\SettingWorkingHours;
use EightyNine\Approvals\Models\ApprovableModel;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use RingleSoft\LaravelProcessApproval\Enums\ApprovalStatusEnum;

class LeaveRequest extends ApprovableModel
{
    use HasFactory, SoftDeletes;

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

    public function processApprovers(): MorphMany
    {
        return $this->morphMany(ProcessApprover::class, 'modelable');
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
            get: fn () => $this->user ? $this->user->full_name : $this->createdBy()->full_name,
        );
    }

    protected function days(): Attribute
    {
        return Attribute::make(
            get: fn () => floatval($this->requestDates()->sum('hours') / app(SettingWorkingHours::class)->day),
        );
    }

    protected function approvers(): Attribute
    {
        return Attribute::make(
            get: function() {
                $approvers = collect();
                foreach($this->processApprovers as $approver){
                    if($approver->user_id){
                        $approvers->push($approver->user);
                    }else{
                        foreach(User::role($approver->role_id)->get() as $user){
                            $approvers->push($user);
                        }
                    }
                }

                return $approvers;
            },
        );
    }

    protected function backDate(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->from_date < $this->created_at ? true : false,
        );
    }
    
}
