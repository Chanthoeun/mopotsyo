<?php

namespace App\Models;

use App\Enums\Gender;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Member extends Model
{
    use HasFactory, SoftDeletes, HasTranslations;

    public $translatable = ['name', 'nickname', 'address'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'member_id',
        'member_type_id',
        'name',
        'nickname',
        'gender',
        'date_of_birth',
        'nationality_id',
        'address',
        'village_id',
        'commune_id',
        'district_id',
        'province_id',
        'telephone',
        'photo',
        'status',        
        'account_id',
        'interviewed_id',
        'interviewed_at',
        'verified_id',
        'verified_at',
        'user_id',

    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id'                => 'integer',
        'member_type_id'    => 'integer',
        'gender'            => Gender::class,
        'date_of_birth'     => 'date',
        'nationality_id'    => 'integer',
        'village_id'        => 'integer',
        'commune_id'        => 'integer',
        'district_id'       => 'integer',
        'province_id'       => 'integer',
        'status'            => 'boolean', 
        'account_id'        => 'integer',
        'interviewed_id'    => 'integer',
        'interviewed_at'    => 'datetime',
        'verified_id'       => 'integer',
        'verified_at'       => 'datetime',       
        'user_id'           => 'integer',
    ];

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function memberType(): BelongsTo
    {
        return $this->belongsTo(MemberType::class);
    }

    public function nationality(): BelongsTo
    {
        return $this->belongsTo(Nationality::class);
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
    
    public function account(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function interviewedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'interviewed_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'verified_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
