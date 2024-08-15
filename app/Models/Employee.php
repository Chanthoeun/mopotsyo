<?php

namespace App\Models;

use App\Enums\GenderEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Employee extends Model
{
    use HasFactory, SoftDeletes, HasTranslations;

    public $translatable = ['name', 'nickname', 'address'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'employee_id',
        'name',
        'nickname',
        'gender',
        'date_of_birth',
        'nationality',
        'email',
        'telephone',
        'address',
        'village_id',
        'commune_id',
        'district_id',
        'province_id',
        'photo',
        'resign_date',
        'user_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'gender' => GenderEnum::class,
        'date_of_birth' => 'date',
        'village_id' => 'integer',
        'commune_id' => 'integer',
        'district_id' => 'integer',
        'province_id' => 'integer',
        'user_id' => 'integer',
    ];

    public function contracts(): HasMany
    {
        return $this->hasMany(EmployeeContract::class);
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function commune(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
