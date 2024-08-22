<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\LeaveRequestRule;
use App\Models\LeaveType;
use App\Models\User;

class LeaveRequestRuleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = LeaveRequestRule::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'leave_type_id' => LeaveType::factory(),
            'name' => $this->faker->name(),
            'description' => $this->faker->text(),
            'from_amount' => $this->faker->randomDigitNotNull(),
            'to_amount' => $this->faker->randomDigitNotNull(),
            'day_in_advance' => $this->faker->randomDigitNotNull(),
            'contract_types' => '{}',
            'user_id' => User::factory(),
        ];
    }
}
