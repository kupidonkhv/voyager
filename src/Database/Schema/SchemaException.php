<?php

namespace TCG\Voyager\Database\Schema;

class SchemaException extends \RuntimeException
{
    public static function tableDoesNotExist($table)
    {
        return new static("There is no table with name `{$table}` in the schema.");
    }

    public static function tableAlreadyExists($table)
    {
        return new static("The table with name `{$table}` already exists.");
    }
}
