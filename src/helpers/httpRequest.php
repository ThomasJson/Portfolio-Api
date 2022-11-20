<?php

namespace Helpers;

class HttpRequest
{
    public string $stringRequest;
    public string $method;
    public array $route;

    private function __construct()
    {
        $this->stringRequest = $_SERVER['REQUEST_METHOD'] . "/" . filter_var(trim($_SERVER["REQUEST_URI"], '/'), FILTER_SANITIZE_URL);
        $requestArray = explode('/', $this->stringRequest);

        $this->method = array_shift($requestArray);
        if ($_ENV['current'] == 'dev' && $_SERVER['HTTP_HOST'] == 'localhost') {
            array_shift($requestArray);
        }
        $this->route = $requestArray;
    }

    private static $instance;

    public static function instance(): HttpRequest
    {
        if (is_null(self::$instance)) {
            self::$instance = new HttpRequest();
        }
        return self::$instance;
    }
}
