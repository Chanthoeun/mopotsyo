<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('option.allow_add_user', true);
        $this->migrator->add('option.allow_work_from_home', true);
        $this->migrator->add('option.allow_switch_day_work', true);        
    }
};
