<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ingredient;
use App\Models\IngredientPurchase;
use App\Models\PendingIngredient;
use App\Models\PurchaseHistory;
use App\Jobs\CheckPendingIngredients;
use App\Http\Requests\CheckIngredientsRequest;


use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WarehouseController extends Controller
{
    public function checkIngredients(CheckIngredientsRequest $request)
    {
        $ingredients = $request->input('ingredients'); // array of ['ingredient_name' => 'quantity']
        $allAvailable = true;

        foreach ($ingredients as $ingredient) {
            $ingredientName = $ingredient['ingredient_name'];
            $quantity = $ingredient['quantity'];

            $ingredientRecord = Ingredient::where('name', $ingredientName)->first();

            if (!$ingredientRecord || $ingredientRecord->quantity < $quantity) {
                // shop ingredient from market
                $marketResponse = $this->purchaseIngredientFromMarket($ingredientName);
                // update ingredient quantity
                $quantity_actual = $ingredientRecord->quantity + $marketResponse['quantitySold'];
                Ingredient::updateOrCreate(
                    ['name' => $ingredientName],
                    ['quantity' => $quantity_actual]
                );

                if ($quantity_actual < $quantity) {
                    // add pending ingredient
                    $this->addPendingIngredient($ingredientName, $quantity - $quantity_actual);

                    // check if all ingredients are available
                    $allAvailable = false;
                }
            }
        }
        return response()->json([
            'available' => $allAvailable
        ]);
    }

    public function decrementIngredients(CheckIngredientsRequest $request)
    {
        $ingredients = $request->input('ingredients'); // array of ['ingredient_name' => 'quantity']

        foreach ($ingredients as $ingredient) {
            $ingredientName = $ingredient['ingredient_name'];
            $quantity = $ingredient['quantity'];

            $ingredientRecord = Ingredient::where('name', $ingredientName)->first();

            if ($ingredientRecord && $ingredientRecord->quantity >= $quantity) {
                $ingredientRecord->quantity -= $quantity;
                $ingredientRecord->save();
            }
        }

        return response()->json(['message' => 'Ingredients decremented successfully']);
    }


    public function purchaseIngredientFromMarket($ingredientName)
    {
        // no se detalla el proceso de autenticacion para el consumo del api//
        $response = Http::post('https://recruitment.alegra.com/api/farmers-market/buy', [
            'ingredient' => $ingredientName
        ]);
        // $quantitySold = $response->json()['quantitySold'] ?? 0;
        $quantitySold = random_int(0, 10);

        // registrer purchase history
        PurchaseHistory::create([
            'ingredient_name' => $ingredientName,
            'quantity' => $quantitySold,
            'status' => $quantitySold > 0 ? 'successful' : 'unsuccessful'
        ]);
        return ['quantitySold' => $quantitySold];
    }

    public function addPendingIngredient($ingredientName, $requiredQuantity)
    {
        $pendingIngredient = PendingIngredient::firstOrNew(['ingredient_name' => $ingredientName]);
        $pendingIngredient->required_quantity += $requiredQuantity;
        $pendingIngredient->save();
    }

    public function runJob()
    {
        CheckPendingIngredients::dispatch();
        return response()->json(['message' => 'Job dispatched']);
    }
    public function getAvailableIngredients()
    {
        // get all available ingredients
        $ingredients = Ingredient::all(['name', 'quantity']);

        return response()->json($ingredients);
    }
    public function getPurchaseHistory()
    {
        // get all purchase history
        $purchaseHistory = PurchaseHistory::all();

        return response()->json($purchaseHistory);
    }
}
