<?php

namespace App\Filament\Admin\Resources\EmployeeResource\Pages;

use App\Filament\Admin\Resources\EmployeeResource;
use App\Models\User;
use App\Notifications\WelcomeNotification;
use App\Settings\SettingOptions;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateEmployee extends CreateRecord
{
    use CreateRecord\Concerns\Translatable;

    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),            
        ];
    }  
    
    protected function afterCreate(): void
    {
        if(app(SettingOptions::class)->allow_add_user == true){
            $password = Str::password(12); // generate a default password with length of 12 caracters
            // create user account
            $user =User::updateOrCreate([
                'email' => $this->record->email
            ],[
                'name'      => $this->record->getTranslations('name'),
                'username'  => $this->record->employee_id,
                'email'     => $this->record->email, 
                'password'  => $password,
                'email_verified_at' => now(),
                'force_renew_password' => true
            ])->assignRole('employee');

            // update employee
            $this->record->update([
                'user_id'   => $user->id,
            ]);

            $user->notify(new WelcomeNotification($password));
        }        
    }
}
