<?php

// http://portfolio-api/article

// "POST/app_user": "$userRole == 1;",
// "POST/app_user/:id": "$userRole == 1 || $userId == $id;",
// "POST/article/:id": "$userRole > 0;",
// "POST/article/*": "$userRole > 0;"

$_ENV['current'] = 'dev';
$config = file_get_contents("src/configs/" . $_ENV["current"] . ".config.json");
$_ENV['config'] = json_decode($config);

if ($_ENV['current'] == 'dev') {
    $origin = "http://localhost:3000";
} else if ($_ENV['current'] == 'prod') {
    $origin = "http://nomdedomaine.com";
}

header("Access-Control-Allow-Origin: $origin");
header('Access-Control-Allow-Headers: Authorization');
header("Access-Control-Allow-Credentials: true");
header ("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, OPTIONS");

require_once 'autoload.php';

use Helpers\HttpRequest;
use Helpers\HttpResponse;
use Controllers\DatabaseController;
use Controllers\AuthController;
use Tools\Initializer;
use Middlewares\AuthMiddleware;

if($_SERVER['REQUEST_METHOD'] == "OPTIONS"){
    header('HTTP/1.0 200 OK');
    die;
}

$request = HttpRequest::instance();

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

// ---------------------------- Login / Register ---------------------------------

if ($_ENV['current'] == 'dev' && !empty($request->route) && $request->route[0] == 'auth') {
    $authController = new AuthController($request);
    $result = $authController->execute();

    if ($result) {
        HttpResponse::send(["data" => $result], 200);
    }
}

// ---------------------------------- AUTH ---------------------------------------

$authMiddleware = new AuthMiddleware($request);
$authMiddleware->verify();

// ---------------------------------- CRUD ---------------------------------------
// 
if ($_ENV['current'] == 'dev' && !empty($request->route) && $request->route[0] !== 'auth' && $request->route[1] !== "0") {
    $controller = new DatabaseController($request);
    $result = $controller->execute();

    if ($result) {
        HttpResponse::send(["data" => $result], 200);
    }
}