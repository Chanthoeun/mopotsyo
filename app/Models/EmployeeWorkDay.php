<?php

namespace App\Models;

use App\Enums\DayOfWeekEnum;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeWorkDay extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'employee_id',
        'contract_id',
        'day_name',
        'start_time',
        'end_time',
        'break_time',
        'break_from',
        'break_to',
        'is_active',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'employee_id' => 'integer',
        'contract_id' => 'integer',
        'day_name' => DayOfWeekEnum::class,
        'break_time' => 'float',
        'is_active' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(EmployeeContract::class);
    }

    // protected function dayName(): Attribute
    // {
    //     return Attribute::make(
    //         get: fn ($value) => match ($value) {
    //             1 => __('field.days.monday'),
    //             2 => __('field.days.tuesday'),
    //             3 => __('field.days.wednesday'),
    //             4 => __('field.days.thursday'),
    //             5 => __('field.days.friday'),
    //             6 => __('field.days.saturday'),
    //             0 => __('field.days.sunday'),
    //         },

    //         set: fn ($value) => match (strtolower($value)) {
    //             'monday' => 1,
    //             'tuesday' => 2,
    //             'wednesday' => 3,
    //             'thursday' => 4,
    //             'friday' => 5,
    //             'saturday' => 6,
    //             'sunday' => 0,
    //         }
    //     );
    // }
}
