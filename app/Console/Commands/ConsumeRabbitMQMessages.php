<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RabbitMQService;
use App\Services\IngredientService;
use App\Jobs\VerifyIngredientsJob;
use Illuminate\Support\Facades\Log;

class ConsumeRabbitMQMessages extends Command
{
    protected $signature = 'rabbitmq:consume';
    protected $description = 'Consume messages from RabbitMQ';

    protected $rabbitMQService;
    protected $ingredientService;

    public function __construct(RabbitMQService $rabbitMQService, IngredientService $ingredientService)
    {
        parent::__construct();
        $this->rabbitMQService = $rabbitMQService;
        $this->ingredientService = $ingredientService;
    }

    public function handle()
    {
        $this->rabbitMQService->consumeMessages('ingredient_verification_queue', [$this, 'processMessage']);
    }

    public function processMessage($msg)
    {

        try {
            if (!is_object($msg) || !isset($msg->body)) {
                throw new \Exception('Invalid message structure: ' . print_r($msg, true));
            }

            $data = json_decode($msg->body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Error decoding JSON: ' . json_last_error_msg());
            }


            dispatch(new VerifyIngredientsJob($data));
        } catch (\Exception $e) {
            Log::error('Error processing message: ' . $e->getMessage());
        }
    }

}
