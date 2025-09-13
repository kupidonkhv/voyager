<?php

namespace TCG\Voyager\Database\Types\Common;

use TCG\Voyager\Database\Types\Type;

class DecimalType extends Type
{
    public const NAME = "decimal";
    public const DBTYPE = "decimal";

    public function getName()
    {
        return static::NAME;
    }

    public function getSQLDeclaration(array $fieldDeclaration)
    {
        return "DECIMAL";
    }
}
