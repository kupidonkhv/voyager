<?php

namespace TCG\Voyager\Database\Types\Common;

use TCG\Voyager\Database\Types\Type;

class DoubleType extends Type
{
    public const NAME = 'double';
    public const DBTYPE = 'double';

    public function getName()
    {
        return static::NAME;
    }

    public function getSQLDeclaration(array $fieldDeclaration)
    {
        return 'DOUBLE';
    }
}
