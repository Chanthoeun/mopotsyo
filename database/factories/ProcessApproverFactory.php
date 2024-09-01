<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\LeaveRequest;
use App\Models\ProcessApprover;
use App\Models\Role;
use App\Models\User;

class ProcessApproverFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ProcessApprover::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'step_id' => $this->faker->randomNumber(),
            'leave_request_id' => LeaveRequest::factory(),
            'role_id' => Role::factory(),
            'user_id' => User::factory(),
        ];
    }
}
