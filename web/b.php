<?php
require '../vendor/autoload.php'; // Include Composer's autoloader

$client = new MongoDB\Client("mongodb://mongodb:27017");

$collection = $client->hellofresh->recipes;
//var_dump($client);
$result = $collection->find();
foreach ($result as $entry) {
    var_dump($entry);
}
