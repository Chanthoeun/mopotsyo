<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Employee;
use App\Models\Location;
use App\Models\User;

class EmployeeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Employee::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'employee_id' => $this->faker->regexify('[A-Za-z0-9]{20}'),
            'name' => $this->faker->name(),
            'nickname' => $this->faker->word(),
            'gender' => $this->faker->regexify('[A-Za-z0-9]{6}'),
            'date_of_birth' => $this->faker->date(),
            'nationality' => $this->faker->regexify('[A-Za-z0-9]{3}'),
            'email' => $this->faker->safeEmail(),
            'telephone' => $this->faker->word(),
            'address' => $this->faker->word(),
            'village_id' => Location::factory(),
            'commune_id' => Location::factory(),
            'district_id' => Location::factory(),
            'province_id' => Location::factory(),
            'photo' => $this->faker->word(),
            'resign_date' => $this->faker->date(),
            'status' => $this->faker->boolean(),
            'user_id' => User::factory(),
        ];
    }
}
