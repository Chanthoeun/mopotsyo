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
