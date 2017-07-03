<?php

require __DIR__.'/../vendor/autoload.php';

$consumerKey = 'YOUR_API_KEY';
$consumerSecret = 'YOUR_API_SECRET';

$client = new \Kaskus\KaskusClient($consumerKey, $consumerSecret);

try {
    $response = $client->get('v1/hot_threads');
    $forumList = $response->json();
    print_r($forumList);
} catch (\Kaskus\Exceptions\KaskusRequestException $exception) {
    // Kaskus Api returned an error
    echo $exception->getMessage();
} catch (\Exception $exception) {
    // some other error occured
    echo $exception->getMessage();
}

echo "\n";






