<?php

namespace TCG\Voyager\Database\Types\Common;

use TCG\Voyager\Database\Types\Type;

class IntegerType extends Type
{
    public const NAME = "integer";
    public const DBTYPE = "integer";

    public function getName()
    {
        return static::NAME;
    }

    public function getSQLDeclaration(array $fieldDeclaration)
    {
        // Basic integer type declaration
        $sql = "INTEGER";
        
        if (!empty($fieldDeclaration["unsigned"])) {
            $sql .= " UNSIGNED";
        }
        
        if (!empty($fieldDeclaration["autoincrement"])) {
            $sql .= " AUTO_INCREMENT";
        }
        
        return $sql;
    }
}
