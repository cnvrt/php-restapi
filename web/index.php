<?php

require '../vendor/autoload.php';
require_once '../env.php';

use MongoDB\Client;

// $client = new Client("mongodb+srv://{$_ENV['MONGO_USER']}:{$_ENV['MONGO_PASS']}@cluster0.i2re1dg.mongodb.net/");
// $client = new Client("mongodb://{$_ENV['MONGO_USER']}:{$_ENV['MONGO_PASS']}@mongodb:27017");
$client = new Client("mongodb://mongodb:27017");
$collection = $client->hellofresh->recipes;


http_response_code(200);
$body = [];
$path = parse_url( str_replace('//', '/', $_SERVER['REQUEST_URI'] ) )['path'];
$uripath = rtrim($path,"/");
$uripath = $uripath==""?"/":$uripath;

$mthd = $_SERVER['REQUEST_METHOD'];

switch ($uripath) {
    case '/':
        $body = [];
        break;
        
    case '/recipes':
        if ($mthd === 'GET') {
            $recipes = $collection->find()->toArray();
            $body = $recipes;
        } elseif ($mthd === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $result = $collection->insertOne($data);
            $body = ['message' => 'Recipe created', '_id' => (string)$result->getInsertedId(), "data" => $data ];
        }
        break;

    case preg_match('/\/recipes\/([0-9a-fA-F]{24})\/rating/', $uripath, $matches) && $mthd === 'POST' ? true : false:
        $recipeId = $matches[1];
        if ($mthd === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $data['recipe_id'] = new MongoDB\BSON\ObjectId($recipeId);
            $client->hellofresh->ratings->insertOne($data);
            $body = ['message' => 'Rating added', 'data' => $data ];
        }
        break;

    case preg_match('/\/recipes\/([0-9a-fA-F]{24})/', $uripath, $matches) && $mthd === 'GET' || $mthd === 'PUT' || $mthd === 'DELETE' ? true : false:
        $recipeId = $matches[1];
        if ($mthd === 'GET') {
            $recipe = $collection->findOne(['_id' => new MongoDB\BSON\ObjectId($recipeId)]);
            $body = $recipe;
        } elseif ($mthd === 'PUT' || $mthd === 'PATCH') {
            $data = json_decode(file_get_contents('php://input'), true);
            $collection->updateOne(['_id' => new MongoDB\BSON\ObjectId($recipeId)], ['$set' => $data]);
            $body = ['message' => 'Recipe updated'];
        } elseif ($mthd === 'DELETE') {
            $collection->deleteOne(['_id' => new MongoDB\BSON\ObjectId($recipeId)]);
            $body = ['message' => 'Recipe deleted'];
        }
        break;

    case '/search':
        if ($mthd === 'GET') {
            $body = $collection->find(['name' => new MongoDB\BSON\Regex($_GET['q'], 'i')])->toArray();
        }
        break;

    default:
        http_response_code(404);
        $body = ['message' => 'Not Found'];
        break;
}


header('Content-Type: application/json');
echo json_encode($body);
