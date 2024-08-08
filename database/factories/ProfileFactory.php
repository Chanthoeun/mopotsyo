<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Department;
use App\Models\Profile;
use App\Models\Shift;
use App\Models\User;

class ProfileFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Profile::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'photo' => $this->faker->word(),
            'gender' => $this->faker->regexify('[A-Za-z0-9]{6}'),
            'date_of_birth' => $this->faker->date(),
            'resign_date' => $this->faker->date(),
            'position' => $this->faker->word(),
            'address' => $this->faker->word(),
            'telephone' => $this->faker->word(),
            'status' => $this->faker->boolean(),
            'supervisor_id' => User::factory(),
            'department_id' => Department::factory(),
            'shift_id' => Shift::factory(),
            'user_id' => User::factory(),
        ];
    }
}
