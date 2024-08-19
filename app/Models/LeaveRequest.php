<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveRequest extends Model
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
        'status',
        'user_id',
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
        'status' => 'integer',
        'user_id' => 'integer',
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

    public function actionStatuses(): MorphMany
    {
        return $this->morphMany(ActionStatus::class, 'actionstatusable');
    }

    public function leaverequestable(): MorphTo
    {
        return $this->morphTo();
    }
}