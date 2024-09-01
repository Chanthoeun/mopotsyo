<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\RequestDate;

class RequestDateFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = RequestDate::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'date' => $this->faker->date(),
            'start_time' => $this->faker->time(),
            'end_time' => $this->faker->time(),
            'hours' => $this->faker->randomDigitNotNull(),
        ];
    }
}
