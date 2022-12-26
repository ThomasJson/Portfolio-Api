public function insertOrUpdate(array $body)
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
            // if (true)
            $rows = $this->selectWhere("$this->pk IN ($inClause)", $modelList->idList());
            // $rows = Tableau contenant les deux Std Class
            return $rows;
        }

        return null;
    }