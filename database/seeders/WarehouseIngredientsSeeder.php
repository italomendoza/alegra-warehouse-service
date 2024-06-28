<?php

namespace Database\Seeders;
// database/seeders/WarehouseIngredientsSeeder.php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WarehouseIngredientsSeeder extends Seeder
{
    public function run()
    {
        $ingredients = [
            ['name' => 'tomato', 'quantity' => 5],
            ['name' => 'lemon', 'quantity' => 5],
            ['name' => 'potato', 'quantity' => 5],
            ['name' => 'rice', 'quantity' => 5],
            ['name' => 'ketchup', 'quantity' => 5],
            ['name' => 'lettuce', 'quantity' => 5],
            ['name' => 'onion', 'quantity' => 5],
            ['name' => 'cheese', 'quantity' => 5],
            ['name' => 'meat', 'quantity' => 5],
            ['name' => 'chicken', 'quantity' => 5],
        ];

        DB::table('ingredients')->insert($ingredients);
    }
}

