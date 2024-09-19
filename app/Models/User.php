<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Carbon\Carbon;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Mchev\Banhammer\Traits\Bannable;
use PhpOffice\PhpSpreadsheet\Calculation\DateTimeExcel\WorkDay;
use Rappasoft\LaravelAuthenticationLog\Traits\AuthenticationLoggable;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Translatable\HasTranslations;
use Yebor974\Filament\RenewPassword\Contracts\RenewPasswordContract;
use Yebor974\Filament\RenewPassword\RenewPasswordPlugin;
use Yebor974\Filament\RenewPassword\Traits\RenewPassword;

class User extends Authenticatable implements FilamentUser, RenewPasswordContract, HasName
{
    use Bannable, HasRoles, HasFactory, Notifiable, AuthenticationLoggable, SoftDeletes, HasTranslations, RenewPassword;

    public $translatable = ['name'];
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
        // return str_ends_with($this->email, '@yourdomain.com') && $this->hasVerifiedEmail();
    }

    public function getFilamentName(): string
    {
        return "{$this->name}";
    }

    // renew password
    public function needRenewPassword(): bool
    {
        $plugin = RenewPasswordPlugin::get();
    
        return
            (
                !is_null($plugin->getPasswordExpiresIn())
                && Carbon::parse($this->{$plugin->getTimestampColumn()})->addDays($plugin->getPasswordExpiresIn()) < now()
            ) || (
                $plugin->getForceRenewPassword()
                && $this->{$plugin->getForceRenewColumn()}
            );
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class, 'supervisor_id');
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }    

    public function entitlements(): HasMany
    {
        return $this->hasMany(LeaveEntitlement::class);
    }


    public function carryForwards(): HasMany
    {
        return $this->hasMany(LeaveCarryForward::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function overtimes(): HasMany
    {
        return $this->hasMany(OverTime::class);
    }


    public function activityLogs(): MorphMany
    {
        return $this->morphMany(Activity::class, 'causer');
    }

    protected function contract(): Attribute
    {
        return Attribute::make(
            get: function () {
                if(empty($this->employee)) return null;

                return $this->employee->contracts()->where('is_active', true)->first();
            },
        );
    }
    
    protected function supervisor(): Attribute
    {
        return Attribute::make(
            get: function(){   
                return $this->contract->supervisor ?? null;                
            },
        );
    }

    protected function departmentHead(): Attribute
    {
        return Attribute::make(
            get: function(){   
                return $this->contract->departmentHead ?? null;                
            },
        );
    }

    protected function departmentSupervisor(): Attribute
    {
        return Attribute::make(
            get: function(){           
                if($this->id == $this->contract->department->supervisor->id)
                {
                    return $this->supervisor;
                }   

                return $this->contract->department->supervisor ?? null;
            },
        );
    }    

    protected function hasEntitlement(): Attribute
    {
        return Attribute::make(
            get: function() {
                if($this->entitlements()->count() > 0) return true;

                return false;
            },
        );
    }

    protected function hasCarryForward(): Attribute
    {
        return Attribute::make(
            get: function() {
                if($this->carryForwards()->count() > 0) return true;

                return false;
            },
        );
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->employee->nickname ?? $value,
        );
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->employee->name ?? $value,
        );
    }

    protected function workDays(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->employee->workDays->where('is_active', true),
        );
    }

    protected function subordinators(): Attribute
    {
        return Attribute::make(
            get: function() {
                return $this->employee->contracts;
            },
        );
    }
}
