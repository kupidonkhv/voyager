<?php

namespace TCG\Voyager\Database\Types\Common;

use TCG\Voyager\Database\Types\Type;

class JsonType extends Type
{
    public const NAME = 'json';
    public const DBTYPE = 'json';

    public function getName()
    {
        return static::NAME;
    }

    public function getSQLDeclaration(array $fieldDeclaration)
    {
        return 'JSON';
    }
}
