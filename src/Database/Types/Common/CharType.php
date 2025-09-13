<?php

namespace TCG\Voyager\Database\Types\Common;

use TCG\Voyager\Database\Types\Type;

class CharType extends Type
{
    public const NAME = 'char';
    public const DBTYPE = 'char';

    public function getName()
    {
        return static::NAME;
    }

    public function getSQLDeclaration(array $fieldDeclaration)
    {
        $length = empty($fieldDeclaration['length']) ? 1 : $fieldDeclaration['length'];
        return "CHAR({$length})";
    }
}
