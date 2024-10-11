<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\PurchaseRequest;
use App\Models\User;

class PurchaseRequestFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PurchaseRequest::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'purpose' => $this->faker->text(),
            'for' => $this->faker->word(),
            'location' => $this->faker->word(),
            'expect_date' => $this->faker->date(),
            'user_id' => User::factory(),
        ];
    }
}
