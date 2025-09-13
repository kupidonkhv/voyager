<?php

namespace TCG\Voyager\Database\Types\Common;

use TCG\Voyager\Database\Types\Type;

class DateTimeType extends Type
{
    public const NAME = "datetime";
    public const DBTYPE = "datetime";

    public function getName()
    {
        return static::NAME;
    }

    public function getSQLDeclaration(array $fieldDeclaration)
    {
        return "DATETIME";
    }
}
