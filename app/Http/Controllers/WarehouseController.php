<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ingredient;
use App\Models\PurchaseHistory;
use App\Jobs\CheckPendingIngredients;

class WarehouseController extends Controller
{
    public function runJob()
    {
        CheckPendingIngredients::dispatch();
        return response()->json(['message' => 'Job dispatched']);
    }

    public function getAvailableIngredients()
    {
        $ingredients = Ingredient::all(['name', 'quantity']);
        return response()->json($ingredients);
    }

    public function getPurchaseHistory()
    {
        $purchaseHistory = PurchaseHistory::all();
        return response()->json($purchaseHistory);
    }
}
