<?php

// http://portfolio-api/article

$_ENV['current'] = 'dev';
$config = file_get_contents("src/configs/" . $_ENV["current"] . ".config.json");
$_ENV['config'] = json_decode($config);

if ($_ENV['current'] == 'dev') {
    $origin = "http://localhost:3000";
} else if ($_ENV['current'] == 'prod') {
    $origin = "http://nomdedomaine.com";
}

header("Access-Control-Allow-Origin: $origin");

require_once 'autoload.php';

use Helpers\HttpRequest;
use Helpers\HttpResponse;
use Services\DatabaseService;
use Controllers\DatabaseController;
use Controllers\AuthController;
use Tools\Initializer;
use Models\Model;
use Models\ModelList;

$request = HttpRequest::instance();

// ---------------------------------- TOKEN --------------------------------------

// Créer un Token à partir d'un tableau associatif

// use Helpers\Token;
// $tokenFromDataArray = Token::create(['name' => "Laurent", 'id' => 1234]);
// $encoded = $tokenFromDataArray->encoded;

// $tokenFromEncodedString = Token::create($encoded);
// $decoded = $tokenFromEncodedString->decoded;
// $test = $tokenFromEncodedString->isValid();
// $bp = true;


// ------------------------------ Initializer ------------------------------------

if ($_ENV['current'] == 'dev' && !empty($request->route) && $request->route[0] == 'init') {
    if (Initializer::start($request)) {
        HttpResponse::send(["message" => "Api Initialized"]);
    }
    HttpResponse::send(["message" => "Api Not Initialized, try again ..."]);
}

// ------------------------------ mailerService ----------------------------------

if ($_ENV['current'] == 'dev' && !empty($request->route) && $request->route[0] == 'test') {
    $dbs = new DatabaseController($request);
    $result = $dbs->sendTestMail();

    if ($result) {
        HttpResponse::send(["data" => $result], 200);
    }
}

// ---------------------------------- Login --------------------------------------

if ($_ENV['current'] == 'dev' && !empty($request->route) && $request->method == 'POST') {
    $authController = new AuthController($request);
    $result = $authController->login();

    if ($result) {
        HttpResponse::send(["data" => $result], 200);
    }
}

// ---------------------------------- CRUD ---------------------------------------

if ($_ENV['current'] == 'dev' && !empty($request->route) && $request->method != 'POST') {
    $controller = new DatabaseController($request);
    $result = $controller->execute();

    if ($result) {
        HttpResponse::send(["data" => $result], 200);
    }
}
