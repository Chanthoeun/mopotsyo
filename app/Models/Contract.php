<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Contract extends Model
{
    use HasFactory, SoftDeletes, HasTranslations;

    public $translatable = ['Position'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'contract_type_id',
        'position',
        'start_date',
        'end_date',
        'department_id',
        'supervisor_id',
        'shift_id',
        'contract_no',
        'file',
        'is_active',
        'employee_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'contract_type_id' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'department_id' => 'integer',
        'supervisor_id' => 'integer',
        'shift_id' => 'integer',
        'is_active' => 'boolean',
        'employee_id' => 'integer',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function contractType(): BelongsTo
    {
        return $this->belongsTo(ContractType::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
