<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Carbon\Carbon;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Mchev\Banhammer\Traits\Bannable;
use Rappasoft\LaravelAuthenticationLog\Traits\AuthenticationLoggable;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Translatable\HasTranslations;
use Yebor974\Filament\RenewPassword\Contracts\RenewPasswordContract;
use Yebor974\Filament\RenewPassword\RenewPasswordPlugin;
use Yebor974\Filament\RenewPassword\Traits\RenewPassword;

class User extends Authenticatable implements FilamentUser, RenewPasswordContract
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

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }    

    public function entitlements(): HasMany
    {
        return $this->hasMany(LeaveEntitlement::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }
    
    protected function supervisor(): Attribute
    {
        return Attribute::make(
            get: function(){
                
                $contract = $this->employee->contracts()->where('is_active', true)->first();
                if(empty($contract->supervisor)){
                    return $contract->department->supervisor ?? null;
                }
                return $contract->supervisor;
            },
        );
    }
    
    
}
