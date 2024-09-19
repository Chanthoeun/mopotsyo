<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\LeaveType;

class LeaveTypeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = LeaveType::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'abbr' => $this->faker->regexify('[A-Za-z0-9]{5}'),
            'color' => $this->faker->regexify('[A-Za-z0-9]{7}'),
            'male' => $this->faker->boolean(),
            'female' => $this->faker->boolean(),
            'balance' => $this->faker->randomDigitNotNull(),
            'maximum_balance' => $this->faker->randomDigitNotNull(),
        ];
    }
}
