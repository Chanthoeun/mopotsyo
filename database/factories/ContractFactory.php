<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Contract;
use App\Models\ContractType;
use App\Models\User;

class ContractFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Contract::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'start_date' => $this->faker->date(),
            'end_date' => $this->faker->date(),
            'file' => $this->faker->word(),
            'is_active' => $this->faker->boolean(),
            'user_id' => User::factory(),
            'contract_type_id' => ContractType::factory(),
        ];
    }
}
