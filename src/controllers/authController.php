<?php

namespace Controllers;

use Services\DatabaseService;
use Helpers\HttpRequest;

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

        $users = $dbs->selectWhere("mail = ? AND is_deleted = ?", [$email, 0]);
        // $users = (array) $users[0];

        if (count($users) == 1 && $users[0]->password == $this->body['password']) {

            // $dbs = new DatabaseService("???");
            // $appUser = $dbs->selectOne($accounts[0]->Id_appUser);
            // return ["result" => true, "role" => $appUser->Id_role];

        }
        return ["result" => false];
    }
}
