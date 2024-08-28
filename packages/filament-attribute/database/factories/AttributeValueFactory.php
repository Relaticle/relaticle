<?php

namespace ManukMinasyan\FilamentAttribute\Database\Factories;

use ManukMinasyan\FilamentAttribute\Models\Attribute;
use ManukMinasyan\FilamentAttribute\Models\AttributeValue;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<AttributeValue>
 */
class AttributeValueFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<AttributeValue>
     */
    protected $model = AttributeValue::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'entity_id' => $this->faker->randomNumber(),
            'entity_type' => $this->faker->word(),
            'text_value' => $this->faker->text(),
            'boolean_value' => $this->faker->boolean(),
            'integer_value' => $this->faker->randomNumber(),
            'float_value' => $this->faker->randomFloat(),
            'datetime_value' => Carbon::now(),
            'date_value' => Carbon::now(),
            'json_value' => $this->faker->words(),

            'attribute_id' => Attribute::factory(),
        ];
    }
}
