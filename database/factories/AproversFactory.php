<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Aprovers;
use App\Models\EmployeeContract;
use App\Models\Role;
use App\Models\User;

class AproversFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Aprovers::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'model_type' => $this->faker->word(),
            'role_id' => Role::factory(),
            'approver_id' => User::factory(),
            'contract_id' => EmployeeContract::factory(),
        ];
    }
}
