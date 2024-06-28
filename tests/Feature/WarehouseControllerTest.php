<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\PendingIngredient;
use App\Models\PurchaseHistory;
use App\Jobs\CheckPendingIngredients;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WarehouseControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_check_ingredients_some_not_available()
    {
        // Crear ingredientes en la base de datos
        $ingredientTomato = Ingredient::create(['name' => 'Tomato', 'quantity' => 2]);
        $ingredientLettuce = Ingredient::create(['name' => 'Lettuce', 'quantity' => 0]);

        // Realizar una solicitud POST a la ruta /api/ingredients/check
        $response = $this->postJson('/api/ingredients/check', [
            'ingredients' => [
                ['ingredient_name' => 'Tomato', 'quantity' => 10],
                ['ingredient_name' => 'Lettuce', 'quantity' => 5]
            ]
        ]);

        // Verificar que la respuesta tiene el estado 200
        $response->assertStatus(200)
                 ->assertJson(['available' => false]);

        // Verificar que el ingrediente pendiente fue agregado a la base de datos
        $this->assertDatabaseHas('pending_ingredients', [
            'ingredient_name' => 'Tomato',
            'required_quantity' => 8
        ]);
    }

    public function test_check_ingredients_all_available()
    {
        $ingredient = Ingredient::factory()->create(['name' => 'Tomato', 'quantity' => 20]);

        $response = $this->postJson('/api/ingredients/check', [
            'ingredients' => [
                ['ingredient_name' => 'Tomato', 'quantity' => 10]
            ]
        ]);

        $response->assertStatus(200)
                 ->assertJson(['available' => true]);
    }

    public function test_decrement_ingredients()
    {
        $ingredient = Ingredient::factory()->create(['name' => 'Tomato', 'quantity' => 20]);

        $response = $this->postJson('/api/ingredients/decrement', [
            'ingredients' => [
                ['ingredient_name' => 'Tomato', 'quantity' => 5]
            ]
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Ingredients decremented successfully']);

        $ingredient = Ingredient::find($ingredient->id);
        $this->assertEquals(15, $ingredient->quantity);
    }

    public function test_purchase_ingredient_from_market()
    {
        // Mock de la respuesta HTTP
        Http::fake([
            'https://recruitment.alegra.com/api/farmers-market/buy*' => Http::response(['quantitySold' => 5], 200)
        ]);

        $controller = new \App\Http\Controllers\WarehouseController();
        $response = $controller->purchaseIngredientFromMarket('Tomato');

        $this->assertEquals(['quantitySold' => 5], $response);

        $this->assertDatabaseHas('purchase_histories', [
            'ingredient_name' => 'Tomato',
            'quantity' => 5,
            'status' => 'successful'
        ]);
    }

    public function test_add_pending_ingredient()
    {
        $controller = new \App\Http\Controllers\WarehouseController();
        $controller->addPendingIngredient('Tomato', 10);

        $this->assertDatabaseHas('pending_ingredients', [
            'ingredient_name' => 'Tomato',
            'required_quantity' => 10
        ]);
    }

    public function test_run_job()
    {
        Queue::fake();

        $response = $this->postJson('/api/ingredients/run-job');

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Job dispatched']);

        Queue::assertPushed(CheckPendingIngredients::class);
    }

    public function test_get_available_ingredients()
    {
        $ingredient = Ingredient::factory()->create(['name' => 'Tomato', 'quantity' => 20]);

        $response = $this->getJson('/api/ingredients/available');

        $response->assertStatus(200)
                 ->assertJson([
                     ['name' => 'Tomato', 'quantity' => 20]
                 ]);
    }

    public function test_get_purchase_history()
    {
        PurchaseHistory::factory()->create(['ingredient_name' => 'Tomato', 'quantity' => 5, 'status' => 'successful']);

        $response = $this->getJson('/api/ingredients/purchase-history');

        $response->assertStatus(200)
                 ->assertJson([
                     ['ingredient_name' => 'Tomato', 'quantity' => 5, 'status' => 'successful']
                 ]);
    }
}
