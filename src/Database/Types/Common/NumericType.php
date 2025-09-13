<?php

namespace TCG\Voyager\Database\Types\Common;

use TCG\Voyager\Database\Types\Type;

class NumericType extends Type
{
    public const NAME = 'numeric';
    public const DBTYPE = 'numeric';

    public function getName()
    {
        return static::NAME;
    }

    public function getSQLDeclaration(array $fieldDeclaration)
    {
        return 'NUMERIC';
    }
}
