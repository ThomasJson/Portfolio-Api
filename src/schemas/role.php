<?php namespace Schemas; class Role{    const COLUMNS = [        'Id_role' => ['type' => 'varchar(255)', 'nullable' => '0', 'default' => ''],         'title' => ['type' => 'varchar(50)', 'nullable' => '1', 'default' => ''],         'weight' => ['type' => 'varchar(50)', 'nullable' => '1', 'default' => ''],         'created_at' => ['type' => 'datetime', 'nullable' => '1', 'default' => ''],         'updated_at' => ['type' => 'datetime', 'nullable' => '1', 'default' => ''],         'is_deleted' => ['type' => 'tinyint(1)', 'nullable' => '0', 'default' => '0'],     ];}