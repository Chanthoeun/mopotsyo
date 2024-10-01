<?php

namespace App\Models;

use App\Settings\SettingWorkingHours;
use EightyNine\Approvals\Models\ApprovableModel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkFromHome extends ApprovableModel
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'from_date',
        'to_date',
        'reason',
        'user_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'from_date' => 'date',
        'to_date' => 'date',
        'user_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function requestDates(): MorphMany
    {
        return $this->morphMany(RequestDate::class, 'requestdateable');
    }

    public function processApprovers(): MorphMany
    {
        return $this->morphMany(ProcessApprover::class, 'modelable');
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
}
