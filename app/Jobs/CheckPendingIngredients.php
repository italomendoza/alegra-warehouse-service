<?php

namespace App\Jobs;

use App\Models\Ingredient;
use App\Models\PendingIngredient;
use App\Models\PurchaseHistory;
use App\Http\Controllers\WarehouseController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class CheckPendingIngredients implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $warehouseController;

    public function __construct()
    {
        $this->warehouseController = new WarehouseController();
    }
    public function handle()
    {
        $pendingIngredients = PendingIngredient::all();
        $removePendingIngredients = false;
        foreach ($pendingIngredients as $pendingIngredient) {
            $quantitySold = $this->warehouseController->purchaseIngredientFromMarket($pendingIngredient->ingredient_name);
            $quantitySold = $quantitySold['quantitySold'];
            if ($quantitySold > 0) {
                $ingredient = Ingredient::firstOrCreate(['name' => $pendingIngredient->ingredient_name]);
                $ingredient->quantity += $quantitySold;

                $ingredient->save();

                if ($quantitySold >= $pendingIngredient->required_quantity) {
                    $pendingIngredient->delete();
                    $removePendingIngredients = true;
                }
            }
        }
        if($removePendingIngredients){
            // Notify the kitchen service here
            $this->notifyKitchen();
        }
    }

    private function notifyKitchen()
    {
        // Notify the kitchen service about the available ingredients
        Http::post('http://kitchen-service/api/kitchen/retry-pending-orders');
    }
}
