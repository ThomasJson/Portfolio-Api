<?php

namespace Controllers;

use Services\DatabaseService;
use Helpers\HttpRequest;
use Helpers\Token;

class AuthController
{

    public function __construct(HttpRequest $request)
    {
        $this->table = $request->route[0];
        $this->pk = "Id_" . $this->table; // Clé
        $this->id = isset($request->route[1]) ? $request->route[1] : null; // Valeur

        $request_body = file_get_contents('php://input');
        $this->body = json_decode($request_body, true) ?: [];

        $this->action = $request->method;
    }

    public function login()
    {
        $dbs = new DatabaseService($this->table);

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

            $tokenFromEncodedString = Token::create($encoded);
            $decoded = $tokenFromEncodedString->decoded;
            $test = $tokenFromEncodedString->isValid();

            if($test == true) {
                return ["result" => true, "role" => $role[0]->weight, "token" => $encoded];
            }

        }

        return ["result" => false];
    }
}
