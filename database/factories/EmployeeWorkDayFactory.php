<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\EmployeeWorkDay;

class EmployeeWorkDayFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = EmployeeWorkDay::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'contract_id' => EmployeeContract::factory(),
            'day_name' => $this->faker->randomDigitNotNull(),
            'start_time' => $this->faker->time(),
            'end_time' => $this->faker->time(),
            'break_time' => $this->faker->randomFloat(0, 0, 9999999999.),
            'break_from' => $this->faker->time(),
            'break_to' => $this->faker->time(),
            'is_active' => $this->faker->boolean(),
        ];
    }
}
