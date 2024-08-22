<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class ContractType extends Model
{
    use HasFactory, SoftDeletes, HasTranslations;

    public $translatable = [
        'name',
    ];
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'abbr',
        'allow_leave_request',
        'leave_types'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'allow_leave_request' => 'boolean',
        'leave_types' => 'array',
    ];

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }
}
