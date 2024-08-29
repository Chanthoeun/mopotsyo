<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;

class LeaveRequestFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = LeaveRequest::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'leave_type_id' => LeaveType::factory(),
            'from_date' => $this->faker->date(),
            'to_date' => $this->faker->date(),
            'reason' => $this->faker->text(),  
            'is_completed' => $this->faker->boolean(),        
        ];
    }
}
