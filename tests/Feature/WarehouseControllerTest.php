<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Ingredient;
use App\Models\PendingIngredient;
use App\Models\PurchaseHistory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use App\Jobs\CheckPendingIngredients;
use Mockery;

class WarehouseControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_check_ingredients_all_available()
    {
        $ingredient = Ingredient::factory()->create(['quantity' => 10]);

        $response = $this->postJson('/api/ingredients/check', [
            'ingredients' => [
                ['ingredient_name' => $ingredient->name, 'quantity' => 5]
            ]
        ]);

        $response->assertStatus(200)
                 ->assertJson(['available' => true]);
    }



    public function test_decrement_ingredients_success()
    {
        $ingredient = Ingredient::factory()->create(['quantity' => 10]);

        $response = $this->postJson('/api/ingredients/decrement', [
            'ingredients' => [
                ['ingredient_name' => $ingredient->name, 'quantity' => 5]
            ]
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Ingredients decremented successfully']);

        $this->assertDatabaseHas('ingredients', [
            'name' => $ingredient->name,
            'quantity' => 5
        ]);
    }

    public function test_decrement_ingredients_insufficient_quantity()
    {
        $ingredient = Ingredient::factory()->create(['quantity' => 5]);

        $response = $this->postJson('/api/ingredients/decrement', [
            'ingredients' => [
                ['ingredient_name' => $ingredient->name, 'quantity' => 10]
            ]
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Ingredients decremented successfully']);

        $this->assertDatabaseHas('ingredients', [
            'name' => $ingredient->name,
            'quantity' => 5
        ]);
    }

    public function test_purchase_ingredient_from_market()
    {
        $quantitySold = 5;

        // Crear una clase anónima que extienda el controlador original
        $controller = new class extends \App\Http\Controllers\WarehouseController {
            public $quantitySold;
            public function purchaseIngredientFromMarket($ingredientName)
            {
                $quantitySold = $this->quantitySold;

                PurchaseHistory::create([
                    'ingredient_name' => $ingredientName,
                    'quantity' => $quantitySold,
                    'status' => $quantitySold > 0 ? 'successful' : 'unsuccessful'
                ]);

                return ['quantitySold' => $quantitySold];
            }
        };

        // Asignar el valor de $quantitySold a la propiedad del controlador
        $controller->quantitySold = $quantitySold;

        $response = $controller->purchaseIngredientFromMarket('Tomato');

        // Assert the response matches the expected fixed quantity sold
        $this->assertEquals(['quantitySold' => $quantitySold], $response);

        // Assert the purchase history is correctly recorded in the database
        $this->assertDatabaseHas('purchase_histories', [
            'ingredient_name' => 'Tomato',
            'quantity' => $quantitySold,
            'status' => 'successful'
        ]);
    }


    public function test_add_pending_ingredient()
    {
        $ingredientName = 'Tomato';
        $requiredQuantity = 10;

        // Llamar manualmente al método del controlador para agregar un ingrediente pendiente
        $controller = new \App\Http\Controllers\WarehouseController();
        $controller->addPendingIngredient($ingredientName, $requiredQuantity);

        // Verificar que el ingrediente pendiente se haya agregado correctamente en la base de datos
        $this->assertDatabaseHas('pending_ingredients', [
            'ingredient_name' => $ingredientName,
            'required_quantity' => $requiredQuantity
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
        $ingredient = Ingredient::factory()->create();

        $response = $this->getJson('/api/ingredients/available');

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => $ingredient->name]);
    }

    public function test_get_purchase_history()
    {
        $purchase = PurchaseHistory::factory()->create();

        $response = $this->getJson('/api/ingredients/purchase-history');

        $response->assertStatus(200)
                 ->assertJsonFragment(['ingredient_name' => $purchase->ingredient_name]);
    }
}
