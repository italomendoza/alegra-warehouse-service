<?php

namespace App\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Illuminate\Support\Facades\Log;

class RabbitMQService
{
    protected $connection;
    protected $channel;

    public function __construct()
    {
        if (env('APP_ENV') === 'build') {
            // No intentar conectar a RabbitMQ durante la fase de construcción
            return;
        }
        $this->connection = new AMQPStreamConnection(
            config('app.host'),
            config('app.port'),
            config('app.user'),
            config('app.password'),
            config('app.vhost')
        );
        $this->channel = $this->connection->channel();

    }

    public function sendMessage($queue, $message)
    {
        $this->channel->queue_declare($queue, false, true, false, false);
        $msg = new AMQPMessage(json_encode($message), ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);
        $this->channel->basic_publish($msg, '', $queue);
    }

    public function consumeMessages($queue, $callback)
    {
        $this->channel->queue_declare($queue, false, true, false, false);
        $this->channel->basic_consume($queue, '', false, true, false, false, $callback);

        while ($this->channel->is_consuming()) {
            $this->channel->wait();
        }
    }



    public function __destruct()
    {
        if (env('APP_ENV') === 'build') {
            return;
        }
        if ($this->channel !== null) {
            $this->channel->close();
        }

        if ($this->connection !== null) {
            $this->connection->close();
        }
    }
}
