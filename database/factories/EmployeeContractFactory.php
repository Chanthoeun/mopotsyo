<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\ContractType;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\Shift;

class EmployeeContractFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = EmployeeContract::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'contract_type_id' => ContractType::factory(),
            'position' => $this->faker->word(),
            'start_date' => $this->faker->date(),
            'end_date' => $this->faker->date(),
            'department_id' => Department::factory(),
            'supervisor_id' => Employee::factory(),
            'shift_id' => Shift::factory(),
            'contract_no' => $this->faker->word(),
            'file' => $this->faker->word(),
            'is_active' => $this->faker->boolean(),
        ];
    }
}
