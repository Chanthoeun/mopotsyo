<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Shift;
use App\Models\ShiftWorkDay;

class ShiftWorkDayFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ShiftWorkDay::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'day' => $this->faker->regexify('[A-Za-z0-9]{20}'),
            'from_time' => $this->faker->time(),
            'to_time' => $this->faker->time(),
            'shift_id' => Shift::factory(),
        ];
    }
}
