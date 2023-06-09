<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MetaPropertydamage>
 */
class MetaPropertyDamageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'property_damage_title' => $this->faker->word,
            'property_damage_desc' => $this->faker->sentence,
            'group_name' => $this->faker->randomElement(['group1', 'group2']),
        ];
    }
}