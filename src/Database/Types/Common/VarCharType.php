<?php

namespace TCG\Voyager\Database\Types\Common;

use TCG\Voyager\Database\Types\Type;

class VarCharType extends Type
{
    public const NAME = 'varchar';
    public const DBTYPE = 'varchar';

    public function getName()
    {
        return static::NAME;
    }

    public function getSQLDeclaration(array $fieldDeclaration)
    {
        $length = empty($fieldDeclaration['length']) ? 255 : $fieldDeclaration['length'];
        return "VARCHAR({$length})";
    }
}
