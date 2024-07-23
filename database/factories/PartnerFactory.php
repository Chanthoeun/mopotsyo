<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Location;
use App\Models\Partner;
use App\Models\PartnerType;

class PartnerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Partner::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'abbr' => $this->faker->regexify('[A-Za-z0-9]{2}'),
            'address' => $this->faker->word(),
            'village_id' => Location::factory(),
            'commune_id' => Location::factory(),
            'district_id' => Location::factory(),
            'province_id' => Location::factory(),
            'map' => $this->faker->word(),
            'is_extended' => $this->faker->boolean(),
            'is_sale' => $this->faker->boolean(),
            'is_active' => $this->faker->boolean(),
            'partner_type_id' => PartnerType::factory(),
        ];
    }
}
