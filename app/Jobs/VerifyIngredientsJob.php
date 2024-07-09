<?php

namespace App\Jobs;

use App\Models\Ingredient;
use App\Models\PendingIngredient;
use App\Services\IngredientService;
use App\Services\RabbitMQService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\App;

class VerifyIngredientsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle()
    {
        $ingredientService = App::make(IngredientService::class);
        $rabbitMQService = App::make(RabbitMQService::class);
        $orderId = $this->data['order_id'];
        $ingredients = $this->data['ingredients'];
        $allAvailable = true;

        foreach ($ingredients as $ingredient) {
            $ingredientName = $ingredient['ingredient_name'];
            $quantity = $ingredient['quantity'];

            $ingredientRecord = Ingredient::where('name', $ingredientName)->first();

            if (!$ingredientRecord || $ingredientRecord->quantity < $quantity) {
                $marketResponse = $ingredientService->purchaseFromMarket($ingredientName);
                $quantity_actual = $ingredientRecord ? $ingredientRecord->quantity + $marketResponse['quantitySold'] : $marketResponse['quantitySold'];
                Ingredient::updateOrCreate(
                    ['name' => $ingredientName],
                    ['quantity' => $quantity_actual]
                );

                if ($quantity_actual < $quantity) {
                    $this->addPendingIngredient($ingredientName, $quantity - $quantity_actual);
                    $allAvailable = false;
                }
            }
        }

        if ($allAvailable) {
            $this->decrementIngredients($ingredients);
            $completionMessage = ['order_id' => $orderId];
            $rabbitMQService->sendMessage('order_completion_queue', $completionMessage);
        }
    }

    protected function addPendingIngredient($ingredientName, $requiredQuantity)
    {
        $pendingIngredient = PendingIngredient::firstOrNew(['ingredient_name' => $ingredientName]);
        $pendingIngredient->required_quantity += $requiredQuantity;
        $pendingIngredient->save();
    }

    protected function decrementIngredients($ingredients)
    {
        foreach ($ingredients as $ingredient) {
            $ingredientName = $ingredient['ingredient_name'];
            $quantity = $ingredient['quantity'];

            $ingredientRecord = Ingredient::where('name', $ingredientName)->first();

            if ($ingredientRecord && $ingredientRecord->quantity >= $quantity) {
                $ingredientRecord->quantity -= $quantity;
                $ingredientRecord->save();
            }
        }
    }
}
