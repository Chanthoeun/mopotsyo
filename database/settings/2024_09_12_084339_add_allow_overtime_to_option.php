<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->deleteIfExists('option.allow_overtime');
        $this->migrator->deleteIfExists('option.overtime_expiry');
        $this->migrator->deleteIfExists('option.overtime_link');

        $this->migrator->add('option.allow_overtime', true);
        $this->migrator->add('option.overtime_expiry', 15);
        $this->migrator->add('option.overtime_link', 1);  
    }
};
