<?php

namespace App\Models;

use App\Settings\SettingWorkingHours;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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

    protected function date(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value,
        );
    }

    protected function day(): Attribute
    {
        return Attribute::make(
            get: fn () => floatval($this->hours / app(SettingWorkingHours::class)->day),
        );
    }
}
