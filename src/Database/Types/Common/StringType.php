<?php

namespace TCG\Voyager\Database\Types\Common;

use TCG\Voyager\Database\Types\Type;

class StringType extends Type
{
    public const NAME = "string";
    public const DBTYPE = "varchar";

    public function getName()
    {
        return static::NAME;
    }

    public function getSQLDeclaration(array $fieldDeclaration)
    {
        $length = $fieldDeclaration["length"] ?? 255;
        return "VARCHAR({$length})";
    }
}
