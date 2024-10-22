<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class EmployeeContract extends Model
{
    use HasFactory, SoftDeletes, HasTranslations;

    public $translatable = ['position'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'employee_id',
        'contract_type_id',
        'position',
        'start_date',
        'end_date',
        'department_id',
        'supervisor_id',
        'department_head_id',
        'shift_id',
        'contract_no',
        'file',
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
        'contract_type_id' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'department_id' => 'integer',
        'supervisor_id' => 'integer',
        'department_head_id' => 'integer',
        'shift_id' => 'integer',
        'is_active' => 'boolean',
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

    public function departmentHead(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function employeeWorkDays(): HasMany
    {
        return $this->hasMany(EmployeeWorkDay::class, 'contract_id');
    }

    public function approvers(): HasMany
    {
        return $this->hasMany(Aprovers::class, 'contract_id');
    }
}
