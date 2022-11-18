<?php

namespace Controllers;

use Services\DatabaseService;
use Helpers\HttpRequest;
use Helpers\Token;

class AuthController
{

    public function __construct(HttpRequest $request)
    {
        $this->controller = $request->route[0];
        // http://portfolio-api/auth

        $this->function = isset($request->route[1]) ? $request->route[1] : null;
        // http://portfolio-api/auth/login

        $request_body = file_get_contents('php://input');
        $this->body = json_decode($request_body, true) ?: [];

        $this->action = $request->method;
        // Methode declarée dans le fetch de react
    }

    public function execute()
    {

        $function = $this->function;
        // $function = /login , /check
        $result = self::$function();
        // self fais référence à la Class en cours, :: signifie utilise la fonction 
        return $result;
    }

    public function login()
    {
        $dbs = new DatabaseService('app_user');

        $email = filter_var($this->body['mail'], FILTER_SANITIZE_EMAIL);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ["result" => false];
        }

        $user = $dbs->selectWhere("mail = ? AND is_deleted = ?", [$email, 0]);
        $prefix = $_ENV['config']->hash->prefix;

        if (count($user) == 1 && password_verify($this->body['password'], $prefix . $user[0]->password)) {

            $dbs = new DatabaseService("role");
            $role = $dbs->selectWhere("Id_role = ? AND is_deleted = ?", [$user[0]->Id_role, 0]);

            // Créer un Token à partir d'un tableau associatif

            $tokenFromDataArray = Token::create(['mail' => $user[0]->mail, 'password' => $user[0]->password]);
            $encoded = $tokenFromDataArray->encoded;

            return ["result" => true, "role" => $role[0]->weight, "id" => $user[0]->Id_app_user, "token" => $encoded];
        }

        return ["result" => false];
    }

    public function check()
    {
        $headers = apache_request_headers();
        if(isset($headers["Authorization"])) {
            $token = $headers["Authorization"];
        }
        
        if (isset($token) && !empty($token)) {
            $tokenFromEncodedString = Token::create($token);
            $decoded = $tokenFromEncodedString->decoded;
            $test = $tokenFromEncodedString->isValid();

            if ($test == true) {
                $dbs = new DatabaseService("app_user");
                $user = $dbs->selectWhere("mail = ? AND is_deleted = ?", [$decoded["mail"], 0]);
                
                $dbs = new DatabaseService("role");
                $role = $dbs->selectWhere("Id_role = ? AND is_deleted = ?", [$user[0]->Id_role, 0]);

                return ["result" => true, "role" => $role[0]->weight, "id" => $user[0]->Id_app_user];
            }

            return ["result" => false];
        }
        
        return ["result" => false];
    }
}
