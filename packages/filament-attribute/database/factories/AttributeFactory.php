<?php

declare(strict_types=1);

namespace ManukMinasyan\FilamentAttribute\Database\Factories;

use ManukMinasyan\FilamentAttribute\Enums\AttributeEntityTypeEnum;
use ManukMinasyan\FilamentAttribute\Enums\AttributeLookupTypeEnum;
use ManukMinasyan\FilamentAttribute\Enums\AttributeTypeEnum;
use ManukMinasyan\FilamentAttribute\Models\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Attribute>
 */
final class AttributeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Attribute>
     */
    protected $model = Attribute::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => $this->faker->word(),
            'name' => $this->faker->name(),
            'type' => $this->faker->randomElement(AttributeTypeEnum::cases()),
            'lookup_type' => $this->faker->randomElement(AttributeLookupTypeEnum::cases()),
            'entity_type' => $this->faker->randomElement(AttributeEntityTypeEnum::cases()),
            'sort_order' => $this->faker->randomNumber(),
            'validation' => $this->faker->word(),
            'is_required' => $this->faker->boolean(),
            'is_unique' => $this->faker->boolean(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
