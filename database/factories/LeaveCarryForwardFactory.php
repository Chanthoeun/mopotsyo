<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\LeaveCarryForward;
use App\Models\LeaveEntitlement;
use App\Models\User;

class LeaveCarryForwardFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = LeaveCarryForward::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'start_date' => $this->faker->date(),
            'end_date' => $this->faker->date(),
            'balance' => $this->faker->randomFloat(0, 0, 9999999999.),
            'leave_entitlement_id' => LeaveEntitlement::factory(),
            'user_id' => User::factory(),
        ];
    }
}
