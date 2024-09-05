<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\ProcessApprovalRule;

class ProcessApprovalRuleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ProcessApprovalRule::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'description' => $this->faker->text(),
            'min' => $this->faker->randomNumber(),
            'max' => $this->faker->randomNumber(),
            'request_in_advance' => $this->faker->randomDigitNotNull(),
            'require_reason' => $this->faker->boolean(),
            'require_attachment' => $this->faker->boolean(),
            'contract_types' => '{}',
            'approval_roles' => '{}',
            'feature' => $this->faker->word(),
        ];
    }
}
