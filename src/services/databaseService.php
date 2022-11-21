<?php

namespace Services;

use Models\Model;
use Models\ModelList;
use PDO;
use PDOException;

class DatabaseService

{
    public ?string $table;
    public string $pk;

    public function __construct(?string $table = null)
    {
        $this->table = $table;
        $this->pk = "Id_" . $this->table;
    }

    private static ?PDO $connection = null;
    private function connect(): PDO

    {
        if (self::$connection == null) {
            $dbConfig = $_ENV["config"]->db;
            $host = $dbConfig->host;
            $port = $dbConfig->port;
            $dbName = $dbConfig->dbName;
            $dsn = "mysql:host=$host;port=$port;dbname=$dbName";
            $user = $dbConfig->user;
            $pass = $dbConfig->pass;
            try {
                $dbConnection = new PDO(
                    $dsn,
                    $user,
                    $pass,
                    array(
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
                    )
                );
            } catch (PDOException $e) {
                die("Erreur de connexion à la base de données :
                $e->getMessage()");
            }

            self::$connection = $dbConnection;
        }

        return self::$connection;
    }

    public function query(string $sql, array $params = []): object
    {
        $statement = $this->connect()->prepare($sql);
        $result = $statement->execute($params);
        return (object)['result' => $result, 'statement' => $statement];
    }

    public static function getTables(): array
    {
        $dbs = new DatabaseService();
        $sql = "SELECT table_name FROM information_schema.tables WHERE table_schema = ?";
        $resp = $dbs->query($sql, ['portfolio-db']);
        $tables = $resp->statement->fetchAll(PDO::FETCH_COLUMN);
        return $tables;
    }

    public function selectWhere(string $where = "1", array $bind = []): array
    {
        $sql = "SELECT * FROM $this->table WHERE $where;";
        $resp = $this->query($sql, $bind);
        $rows = $resp->statement->fetchAll(PDO::FETCH_CLASS);
        return $rows;
    }

    public function getSchema($table)
    {
        $schema = [];
        $sql = "SHOW FULL COLUMNS FROM $table";
        $resp = $this->query($sql, ['portfolio-db']);
        $schema = $resp->statement->fetchAll(PDO::FETCH_ASSOC);
        return $schema;
    }

    public function insertOrUpdate(array $body): ?array
    {
        $modelList = new ModelList($this->table, $body['items']);
        $inClause = trim(str_repeat(" ?,", count($modelList->items)), ",");
        // $inClause = "?,?"
        $existingRowsList = $this->selectWhere("$this->pk IN ($inClause)", $modelList->idList());
        // $existingRowsList = Les lignes existantes en bdd qui correspondent aux id's d'idList()
        // Ici, $existingRowsList = tableau comprenant un objet standard Class (pcq FETCH_CLASS) :
        // [Id_article: "vxc98765bdxr", title: "Pull Vert", content: null, etc .. ]
        $existingModelList = new ModelList($this->table, $existingRowsList);
        // " On récupère les models correspondants aux lignes existantes en BDD "
        $valuesToBind = [];
        foreach ($modelList->items as &$model) {
            // Pour chaque model de ! $modelList ! ...
            $existingModel = $existingModelList->findById($model->{$this->pk});
            // Dans les models existants, on extrait le model correspondant à l'id
            foreach ($body['items'] as $item) {
                // Pour chaque item du body ..
                if (isset($item[$this->pk]) && $model->{$this->pk} == $item[$this->pk]) {
                    // Si Id_article est set dans le body et que la pk du model en cours = la pk de l'item du body en cours
                    $model = new Model($this->table, array_merge((array)$existingModel, $item));
                    // $model = fusion du model existant et de l'item en cours (l'item json du body de la requête)
                }
            }
            $valuesToBind = array_merge($valuesToBind, array_values($model->data()));
            // $valuesToBind = fusion de $valuesTobind et des valeurs des datas du model en cours 
        }

        $columns = array_keys(Model::getSchema($this->table));
        // On récupère les colonnes contenues dans le schéma du model de la table 
        $values = "(" . trim(str_repeat("?,", count($columns)), ',') . "),";
        // $values = "(?,?,?,?,?,?,?,?),"
        $valuesClause = trim(str_repeat($values, count($body["items"])), ',');
        // $valuesClause = "(?,?,?,?,?,?,?,?),(?,?,?,?,?,?,?,?)"
        $columnsClause = implode(",", $columns);
        // Implode rassemble les éléments d'un tableau en une chaîne de caractères. String separator + array
        // $columnClause = Id_article,title,content,price ...
        $fieldsToUpdate = array_diff($columns, array($this->pk, "is_deleted"));
        // array_diff() compare le tableau $columns avec le 2ème tableau et retourne les valeurs du tableau $columns 
        // qui ne sont pas présentes dans l'autre tableau.
        $updatesClause = "";

        foreach ($fieldsToUpdate as $field) {
            // Pour chaque $field de l'array .. 
            $updatesClause .= "$field = VALUES($field), ";
            // $updatesClause = "title"= VALUES(title), "content"= VALUES(content), ...
        }

        $updatesClause = rtrim($updatesClause, ", ");
        // rtrim — Supprime les espaces (ou d'autres caractères) de fin de chaîne : Ici ", "
        $sql = "INSERT INTO $this->table ($columnsClause) VALUES $valuesClause ON DUPLICATE KEY UPDATE $updatesClause";
        // L’instruction ON DUPLICATE KEY UPDATE est une fonctionnalité de MySQL qui permet de mettre à jour des données 
        // lorsqu’un enregistrement existe déjà dans une table. Cela permet d’avoir qu’une seule requête SQL 
        // pour effectuer selon la convenance un INSERT ou un UPDATE.
        $resp = $this->query($sql, $valuesToBind);

        if ($resp->result) {
            $rows = $this->selectWhere("$this->pk IN ($inClause)", $modelList->idList());
            // $rows = Tableau contenant les deux Std Class
            
            return $rows;
        }
        
        return null;
    }

    public function hardDelete(array $body): ?array
    {
        $modelList = new ModelList($this->table, $body['items']);
        $ids = $modelList->idList();
        $questionMarks = str_repeat("?,", count($ids));
        $questionMarks = "(" . trim($questionMarks, ",") . ")";
        $sql = "DELETE FROM $this->table WHERE is_deleted = ? AND $this->pk IN $questionMarks";
        $valuesToBind = [1];
        foreach ($ids as $id) {
            array_push($valuesToBind, $id);
        }
        $resp = $this->query($sql, $valuesToBind);
        if($resp->result && $resp->statement->rowCount() <= count($ids)){
            $where = "is_deleted = ? AND $this->pk IN $questionMarks";
            $rows = $this->selectWhere($where, $valuesToBind);
            $rows['count'] = $resp->statement->rowCount();
            return $rows;
        }
        return null;
    }
}
