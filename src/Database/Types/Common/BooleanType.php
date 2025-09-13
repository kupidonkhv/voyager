<?php

namespace TCG\Voyager\Database\Types\Common;

use TCG\Voyager\Database\Types\Type;

class BooleanType extends Type
{
    public const NAME = "boolean";
    public const DBTYPE = "boolean";

    public function getName()
    {
        return static::NAME;
    }

    public function getSQLDeclaration(array $fieldDeclaration)
    {
        return "BOOLEAN";
    }
}
