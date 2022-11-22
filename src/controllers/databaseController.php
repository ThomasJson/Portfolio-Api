<?php

namespace Controllers;

use Services\DatabaseService;
use Helpers\HttpRequest;
use Services\MailerService;

class DatabaseController
{

    private string $table;
    private string $pk;
    private ?string $id;
    private array $body;
    private string $action;

    public function __construct(HttpRequest $request)
    {
        $this->table = $request->route[0];
        $this->pk = "Id_" . $this->table; // Clé
        $this->id = isset($request->route[1]) ? $request->route[1] : null; // Valeur

        $request_body = file_get_contents('php://input');
        $this->body = json_decode($request_body, true) ?: [];

        $this->action = $request->method;
    }

    /**
     * Retourne le résultat de la méthode ($action) exécutée
     */
    public function execute(): ?array
    {
        if ($this->action !== "POST") {
            $action = strtolower($this->action);
            $result = self::$action();
        }

        if ($this->action == "POST" && isset($this->id)) {

            if ($this->id == 0) { // POST /table/0
                $result = $this->getAllWith($this->body["with"]);
            }

            // if ($id > 0) { // POST /table/:id
            //     $this->action = $this->getOneWith($id, $this->body["with"]);
            // }
        }

        return $result;
    }

    /**
     * Action exécutée lors d'un GET
     * Retourne le résultat du selectWhere de DatabaseService
     * soit sous forme d'un tableau contenant toutes les lignes (si pas d'id)
     * soit sous forme du tableau associatif correspondant à une ligne (si id)
     */

    private function get(): ?array
    {
        $dbs = new DatabaseService($this->table);
        $datas = $dbs->selectWhere(is_null($this->id) ?: "$this->pk= ?", [$this->id]);
        return $datas;
    }

    private function put(): ?array
    {
        $dbs = new DatabaseService($this->table);
        $rows = $dbs->insertOrUpdate($this->body);
        return $rows;
    }

    private function patch()
    {
        $dbs = new DatabaseService($this->table);
        $rows = $dbs->softDelete($this->body);
        return $rows;
    }

    private function delete(): ?array
    {
        $dbs = new DatabaseService($this->table);
        $rows = $dbs->hardDelete($this->body);
        return $rows;
    }

    function sendTestMail()
    {
        $ms = new MailerService();

        $mailParams = [
            "fromAddress" => ["blog@gmail.com", "newsletter monblog.com"],
            "destAddresses" => ["itstompearson.blog@gmail.com"],
            "replyAddress" => ["blog@gmail.com", "information monblog.com"],
            "subject" => "Newsletter nomblog.com",
            "body" => "This is the HTML message sent by <b>monblog.com</b>",
            "altBody" => "This is the plain text message for non-HTML mail clients"
        ];
        return $ms->send($mailParams);
    }

    function getAllWith($with)
    {
        $dbs = new DatabaseService($this->table);
        $rows = $dbs->selectWhere("is_deleted = ?", [0]);

        $dbsWith = new DatabaseService($with[0]);
        $withRows = $dbsWith->selectWhere("is_deleted = ?", [0]);

        foreach ($rows as $row) {

            $valueToBind = $row->{"Id_" . $with[0]};

            foreach ($withRows as $k) {

                if ($k->{"Id_" . $with[0]} == $valueToBind) {
                    
                    $rowToFind = $dbsWith->selectWhere("Id_" . $with[0] . " = ? AND is_deleted = ?", [$valueToBind, 0]);
                    $row->with = $rowToFind;
                }
            }
        }

        return $rows;
    }
}
