<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Timesheet;
use App\Models\User;

class TimesheetFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Timesheet::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name'  => $this->faker->name(),
            'from_date' => $this->faker->date(),
            'to_date' => $this->faker->date(),
            'reason' => $this->faker->text(),
            'user_id' => User::factory(),
        ];
    }
}
