<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Models\Role;
use Spatie\Translatable\HasTranslations;

class Department extends Model
{
    use HasFactory, SoftDeletes, HasTranslations;

    public $translatable = ['name'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'parent_id',
        'name',
        'is_active',
        'supervisor_id',
        'role_id'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'is_active' => 'boolean',
    ];

    public function children(): HasMany
    {
        return $this->hasMany(Self::class, 'parent_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Self::class, 'parent_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }   
}
