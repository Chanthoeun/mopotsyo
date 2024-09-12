<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->deleteIfExists('workhour.break_time');
        $this->migrator->deleteIfExists('workhour.break_from');
        $this->migrator->deleteIfExists('workhour.break_to');
        $this->migrator->add('workhour.break_time', 1);
        $this->migrator->add('workhour.break_from', '12:00:00');
        $this->migrator->add('workhour.break_to', '13:00:00');
    }
};
