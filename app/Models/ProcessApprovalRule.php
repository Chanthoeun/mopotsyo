<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class ProcessApprovalRule extends Model
{
    use HasFactory, SoftDeletes, HasTranslations;

    public $translatable = ['name', 'description'];
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'min',
        'max',
        'request_in_advance',
        'require_reason',
        'require_attachment',
        'contract_types',
        'approval_roles',
        'feature',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'min' => 'integer',
        'max' => 'integer',
        'request_in_advance' => 'integer',
        'require_reason' => 'boolean',
        'require_attachment' => 'boolean',
        'approval_roles' => 'array',
        'contract_types' => 'array',
    ];
}
