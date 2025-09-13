<?php

namespace TCG\Voyager\Database\Types\Common;

use TCG\Voyager\Database\Types\Type;

class FloatType extends Type
{
    public const NAME = "float";
    public const DBTYPE = "float";

    public function getName()
    {
        return static::NAME;
    }

    public function getSQLDeclaration(array $fieldDeclaration)
    {
        return "FLOAT";
    }
}
