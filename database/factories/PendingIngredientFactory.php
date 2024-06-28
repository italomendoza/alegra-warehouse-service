<?php

namespace Database\Factories;

use App\Models\PendingIngredient;
use Illuminate\Database\Eloquent\Factories\Factory;

class PendingIngredientFactory extends Factory
{
    protected $model = PendingIngredient::class;

    public function definition()
    {
        return [
            'ingredient_name' => $this->faker->word,
            'required_quantity' => $this->faker->numberBetween(1, 100),
        ];
    }
}
