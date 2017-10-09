<?php

require "vendor/autoload.php";
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Elasticsearch\ClientBuilder;

$hosts = [
    getenv('ELASTIC_HOST')
];

$connection = new AMQPStreamConnection( getenv('RMQ_HOST'), 
                                        getenv('RMQ_PORT'), 
                                        getenv('RMQ_USER'), 
                                        getenv('RMQ_PASS'));
$channel = $connection->channel();

$clientBuilder = ClientBuilder::create();   // Instantiate a new ClientBuilder
$clientBuilder->setHosts($hosts);           // Set the hosts
$client = $clientBuilder->build();

$channel->queue_declare(getenv('RMQ_INDEX'), false, false, false, false);

echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";

$callback = function($msg) {
    global $client;
    echo " [x] Received ", $msg->body, "\n";

    $params = [
        'index' => getenv('ELASTIC_INDEX'),
        'type' => 'book',
        'body' => $msg->body
    ];

    $response = $client->index($params);
    print_r($response);

};

$channel->basic_consume(getenv('RMQ_INDEX'), '', false, true, false, false, $callback);

while(count($channel->callbacks)) {
    $channel->wait();
}

$channel->close();
$connection->close();

?>