<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Location;
use App\Models\LocationType;

class LocationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Location::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'parent_id' => Location::factory(),
            'code' => $this->faker->regexify('[A-Za-z0-9]{10}'),
            'name' => $this->faker->name(),
            'location_type_id' => LocationType::factory(),
        ];
    }
}
