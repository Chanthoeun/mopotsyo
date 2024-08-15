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
            'minimum_request_days' => $this->faker->randomDigitNotNull(),
            'balance_increment_period' => $this->faker->regexify('[A-Za-z0-9]{10}'),
            'balance_increment_amount' => $this->faker->randomDigitNotNull(),
            'maximum_balance' => $this->faker->randomDigitNotNull(),
            'allow_carry_forward' => $this->faker->boolean(),
            'carry_forward_duration' => $this->faker->regexify('[A-Za-z0-9]{10}'),
            'allow_advance' => $this->faker->boolean(),
            'advance_limit' => $this->faker->randomDigitNotNull(),
            'allow_accrual' => $this->faker->boolean(),
        ];
    }
}
