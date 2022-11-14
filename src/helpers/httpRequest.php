<?php

namespace Helpers;

class HttpRequest
{

    public string $method;
    public array $route;

    /**
     * Récupère la methode (ex : GET, POST, etc ...) 
     * et les différentes partie de la route sous forme de tableau
     * (ex : ["product", 1])
     */
    private function __construct()
    {
        $request = $_SERVER['REQUEST_METHOD'] . "/" . filter_var(trim($_SERVER["REQUEST_URI"], '/'), FILTER_SANITIZE_URL);
        $requestArray = explode('/', $request);
        $this->method = array_shift($requestArray);
        if ($_ENV['env'] == 'dev' && $_SERVER['HTTP_HOST'] == 'localhost') {
            array_shift($requestArray);
        }
        $this->route = $requestArray;
    }

    private static $instance;

    /**
     * Crée une instance de HttpRequest si $instance est null
     * puis retourne cette instance
     */
    public static function instance(): HttpRequest
    {
        if (is_null(self::$instance)) {
            self::$instance = new HttpRequest();
        }
        return self::$instance;
    }
}
