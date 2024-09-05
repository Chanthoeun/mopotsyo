<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->deleteIfExists('option.cc_emails');
        $this->migrator->add('option.cc_emails', [
            [                
                'model_type' => 'App\Models\LeaveRequest',
                'accounts' => [2],                 
            ]
        ]);
        
    }
};
