<?php


namespace App\Jobs;

use App\Models\Ingredient;
use App\Models\PendingIngredient;
use App\Models\PurchaseHistory;
use App\Services\IngredientService;
use App\Services\RabbitMQService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckPendingIngredients implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $ingredientService;
    protected $rabbitMQService;

    public function __construct()
    {
    }

    public function handle(IngredientService $ingredientService, RabbitMQService $rabbitMQService)
    {
        $pendingIngredients = PendingIngredient::all();
        $removePendingIngredients = false;
        foreach ($pendingIngredients as $pendingIngredient) {
            $quantitySold = $ingredientService->purchaseFromMarket($pendingIngredient->ingredient_name);
            $quantitySold = $quantitySold['quantitySold'];

            if ($quantitySold > 0) {
                $ingredient = Ingredient::firstOrCreate(['name' => $pendingIngredient->ingredient_name]);
                $ingredient->quantity += $quantitySold;
                $ingredient->save();


                if ($quantitySold >= $pendingIngredient->required_quantity) {
                    $pendingIngredient->delete();
                    $removePendingIngredients = true;
                } else {
                    $pendingIngredient->required_quantity -= $quantitySold;
                    $pendingIngredient->save();
                }
            }
        }

        if ($removePendingIngredients) {
            $rabbitMQService->sendMessage('retry_pending_orders_queue', ['message' => 'Retry pending orders']);
        }
    }
}
