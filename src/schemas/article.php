<?php namespace Schemas; class Article{    const COLUMNS = [        'Id_article' => ['type' => 'varchar(255)', 'nullable' => '0', 'default' => ''],         'title' => ['type' => 'varchar(250)', 'nullable' => '1', 'default' => ''],         'content' => ['type' => 'text', 'nullable' => '1', 'default' => ''],         'created_at' => ['type' => 'varchar(255)', 'nullable' => '1', 'default' => ''],         'updated_at' => ['type' => 'varchar(255)', 'nullable' => '1', 'default' => ''],         'is_deleted' => ['type' => 'tinyint(1)', 'nullable' => '0', 'default' => '0'],         'Id_account' => ['type' => 'varchar(255)', 'nullable' => '1', 'default' => ''],         'Id_category' => ['type' => 'varchar(255)', 'nullable' => '1', 'default' => ''],         'Id_image' => ['type' => 'varchar(255)', 'nullable' => '1', 'default' => ''],     ];}