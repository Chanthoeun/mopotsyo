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
    }
};
