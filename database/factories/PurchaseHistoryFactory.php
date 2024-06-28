<?php

namespace Database\Factories;

use App\Models\PurchaseHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseHistoryFactory extends Factory
{
    protected $model = PurchaseHistory::class;

    public function definition()
    {
        return [
            'ingredient_name' => $this->faker->word,
            'quantity' => $this->faker->numberBetween(1, 100),
            'status' => $this->faker->randomElement(['successful', 'unsuccessful']),
        ];
    }
}
