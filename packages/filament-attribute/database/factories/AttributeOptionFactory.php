<?php

declare(strict_types=1);

namespace ManukMinasyan\FilamentAttribute\Database\Factories;

use ManukMinasyan\FilamentAttribute\Models\Attribute;
use ManukMinasyan\FilamentAttribute\Models\AttributeOption;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<AttributeOption>
 */
class AttributeOptionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<AttributeOption>
     */
    protected $model = AttributeOption::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'name' => $this->faker->name(),
            'sort_order' => $this->faker->word(),

            'attribute_id' => Attribute::factory(),
        ];
    }
}
