<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\OverTime;

class OverTimeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = OverTime::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'reason' => $this->faker->text(),
        ];
    }
}
