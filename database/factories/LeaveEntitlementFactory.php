<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\LeaveEntitlement;
use App\Models\LeaveType;
use App\Models\User;

class LeaveEntitlementFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = LeaveEntitlement::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'start_date' => $this->faker->date(),
            'end_date' => $this->faker->date(),
            'balance' => $this->faker->randomDigitNotNull(),
            'is_active' => $this->faker->boolean(),
            'leave_type_id' => LeaveType::factory(),
            'user_id' => User::factory(),
        ];
    }
}
