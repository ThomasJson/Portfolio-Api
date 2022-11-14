<?php

namespace Models;

use Services\DatabaseService;

class ModelList
{
    public string $table;
    public string $pk;
    public array $items; // Liste des instances de la classe Model

    public function __construct(string $table, array $list)
    {
        $this->table = $table;
        $this->pk = 'Id_' . $this->table;
        $this->items = [];

        foreach ($list as $json) {
            $json = (array) $json;
            $model = new Model($table, $json);
            array_push($this->items, $model);
        }
    }

    public static function getSchema($table): array
    {
        $schemaName = "Schemas\\" . ucfirst($table);
        file_exists($schemaName ?: null);
        return $schemaName::COLUMNS;
    }

    // Même principe que pour Model mais sur une liste ($this->items)
    public function data(): array
    {
        $data = [];
        foreach($this->items as $items){
            $cleanData = $items->data();
            array_push($data, $cleanData);
        }
        return $data;
    }

    // Renvoie la liste des id contenus dans $this->items
    public function idList($key = null): array
    {
        $idList = [];
        if (!isset($key)) {
            $key = $this->pk;
        }
        foreach($this->items as $item){
            array_push($idList, $item->$key);
        }
        return $idList;
    }

    // Renvoie l'instance contenue dans $this->items correspondant à $id
    public function findById($id): ?Model
    {
        $key = $this->pk;
        foreach($this->items as $item){

            if($item->$key == $id){
                return $item;
            }
        }
        return null;
    }
}
