<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Location;
use App\Models\Member;
use App\Models\MemberType;
use App\Models\Nationality;
use App\Models\User;

class MemberFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Member::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'member_id' => $this->faker->regexify('[A-Za-z0-9]{20}'),
            'member_type_id' => MemberType::factory(),
            'name' => $this->faker->name(),
            'nickname' => $this->faker->word(),
            'gender' => $this->faker->regexify('[A-Za-z0-9]{6}'),
            'date_of_birth' => $this->faker->date(),
            'nationality_id' => Nationality::factory(),
            'address' => $this->faker->word(),
            'village_id' => Location::factory(),
            'commune_id' => Location::factory(),
            'district_id' => Location::factory(),
            'province_id' => Location::factory(),
            'telephone' => $this->faker->word(),
            'photo' => $this->faker->word(),
            'status' => $this->faker->boolean(),
            'supervisor_id' => User::factory(),
            'user_id' => User::factory(),
        ];
    }
}
