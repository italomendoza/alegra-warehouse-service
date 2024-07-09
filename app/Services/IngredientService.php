<?php
// app/Services/IngredientService.php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\PurchaseHistory;
use Illuminate\Support\Facades\Log;

class IngredientService
{
    public function purchaseFromMarket($ingredientName)
    {
        $response = Http::get('https://recruitment.alegra.com/api/farmers-market/buy', [
            'ingredient' => $ingredientName
        ]);
        $quantitySold = $response->json()['quantitySold'] ?? 0;

        // Register purchase history
        PurchaseHistory::create([
            'ingredient_name' => $ingredientName,
            'quantity' => $quantitySold,
            'status' => $quantitySold > 0 ? 'successful' : 'unsuccessful'
        ]);

        return ['quantitySold' => $quantitySold];
    }
}
