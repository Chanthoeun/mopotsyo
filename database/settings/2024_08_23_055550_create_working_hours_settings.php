<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // delete if exists befor add
        $this->migrator->deleteIfExists('workhour.day');
        $this->migrator->deleteIfExists('workhour.week');
        $this->migrator->deleteIfExists('workhour.work_days');


        $this->migrator->add('workhour.day', 8);
        $this->migrator->add('workhour.week', (8*5));
        $this->migrator->add('workhour.work_days', [
            [
                'day_name' => 1,
                'start_time' => '08:00:00',
                'end_time' => '17:00:00',
                'break_time' => 1,
                'break_from' => '12:00:00',
                'break_to' => '13:00:00',
            ],
            [
                'day_name' => 2,
                'start_time' => '08:00:00',
                'end_time' => '17:00:00',
                'break_time' => 1,
                'break_from' => '12:00:00',
                'break_to' => '13:00:00',
            ],
            [
                'day_name' => 3,
                'start_time' => '08:00:00',
                'end_time' => '17:00:00',
                'break_time' => 1,
                'break_from' => '12:00:00',
                'break_to' => '13:00:00',
            ],
            [
                'day_name' => 4,
                'start_time' => '08:00:00',
                'end_time' => '17:00:00',
                'break_time' => 1,
                'break_from' => '12:00:00',
                'break_to' => '13:00:00',
            ],
            [
                'day_name' => 5,
                'start_time' => '08:00:00',
                'end_time' => '17:00:00',
                'break_time' => 1,
                'break_from' => '12:00:00',
                'break_to' => '13:00:00',
            ]
        ]);
    }
};
