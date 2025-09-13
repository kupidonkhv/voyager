<?php

namespace TCG\Voyager\Database\Types\Common;

use TCG\Voyager\Database\Types\Type;

class DateType extends Type
{
    public const NAME = "date";
    public const DBTYPE = "date";

    public function getName()
    {
        return static::NAME;
    }

    public function getSQLDeclaration(array $fieldDeclaration)
    {
        return "DATE";
    }
}
