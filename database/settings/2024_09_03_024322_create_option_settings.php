<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // delete if exists befor add
        $this->migrator->deleteIfExists('option.allow_add_user');
        $this->migrator->deleteIfExists('option.allow_work_from_home');
        $this->migrator->deleteIfExists('option.allow_switch_day_work');

        $this->migrator->add('option.allow_add_user', true);
        $this->migrator->add('option.allow_work_from_home', true);
        $this->migrator->add('option.allow_switch_day_work', true);   

        $this->migrator->deleteIfExists('option.cc_emails');
        $this->migrator->add('option.cc_emails', [
            [                
                'model_type' => 'App\Models\LeaveRequest',
                'accounts' => [2],                 
            ]
        ]);

        $this->migrator->deleteIfExists('option.allow_overtime');
        $this->migrator->deleteIfExists('option.overtime_expiry');
        $this->migrator->deleteIfExists('option.overtime_link');

        $this->migrator->add('option.allow_overtime', true);
        $this->migrator->add('option.overtime_expiry', 15);
        $this->migrator->add('option.overtime_link', 1);  
             
    }
};
