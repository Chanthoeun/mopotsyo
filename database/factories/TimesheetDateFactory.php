<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Timesheet;
use App\Models\TimesheetDate;

class TimesheetDateFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TimesheetDate::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'date' => $this->faker->date(),
            'type' => $this->faker->randomDigitNotNull(),
            'remark' => $this->faker->text(),
            'timesheet_id' => Timesheet::factory(),
        ];
    }
}
