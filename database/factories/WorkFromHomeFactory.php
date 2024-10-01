<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\WorkFromHome;

class WorkFromHomeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = WorkFromHome::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'from_date' => $this->faker->date(),
            'to_date' => $this->faker->date(),
            'reason' => $this->faker->text(),
            'user_id' => User::factory(),
        ];
    }
}
