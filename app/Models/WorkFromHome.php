<?php

namespace App\Models;

use App\Settings\SettingWorkingHours;
use App\Traits\Approvable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkFromHome extends Model
{
    use HasFactory, SoftDeletes, Approvable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
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
        'from_date' => 'date',
        'to_date' => 'date',
        'status' => \App\Enums\Status::class,
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
}
