<?php

namespace App\Models;

use App\Settings\SettingWorkingHours;
use EightyNine\Approvals\Models\ApprovableModel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OverTime extends ApprovableModel
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'expiry_date',
        'reason',
        'unused',
        'user_id'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'expiry_date' => 'date',
        'unused' => 'boolean'
    ];

    public function requestDates(): MorphMany
    {
        return $this->morphMany(RequestDate::class, 'requestdateable');
    }

    public function leaveRequests(): BelongsToMany
    {
        return $this->belongsToMany(LeaveRequest::class);
    }

    public function processApprovers(): MorphMany
    {
        return $this->morphMany(ProcessApprover::class, 'modelable');
    }

    protected function hours(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->requestDates()->sum('hours'),
        );
    }

    protected function days(): Attribute
    {
        return Attribute::make(
            get: fn () => floatval($this->hours / app(SettingWorkingHours::class)->day),
        );
    }

    protected function requested(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->createdBy()->full_name ?? null,
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
}
