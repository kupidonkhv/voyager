<?php

namespace TCG\Voyager\Database\Schema;

use TCG\Voyager\Database\Types\Type;

class Column
{
    protected $name;
    protected $type;
    protected $options = [];

    public function __construct($name, $type, $options = [])
    {
        $this->name = $name;
        $this->type = $type;
        $this->options = $options;
    }

    public static function make(array $column, string $tableName = null)
    {
        $name = Identifier::validate($column['name'], 'Column');
        $type = $column['type'];
        
        // For Laravel 12 compatibility, use our custom Type system
        if (is_array($type)) {
            $typeName = trim($type['name']);
            $typeObj = Type::getType($typeName);
            if (!$typeObj) {
                throw new \RuntimeException("Type {$typeName} not found");
            }
            $type = $typeObj;
        } elseif (is_string($type)) {
            // Handle string type names
            $typeObj = Type::getType($type);
            if (!$typeObj) {
                throw new \RuntimeException("Type {$type} not found");
            }
            $type = $typeObj;
        }
        
        if (is_object($type)) {
            $type->tableName = $tableName;
        }

        $options = array_diff_key($column, array_flip(['name', 'composite', 'oldName', 'null', 'extra', 'type', 'charset', 'collation']));

        return new self($name, $type, $options);
    }

    public function getName()
    {
        return $this->name;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function getAutoincrement()
    {
        return $this->options['autoincrement'] ?? false;
    }

    public function getNotnull()
    {
        return !($this->options['notnull'] ?? true);
    }

    /**
     * @return array
     */
    public static function toArray($column)
    {
        $columnArr = [
            'name' => $column->getName(),
            'type' => Type::toArray($column->getType()),
            'oldName' => $column->getName(),
            'null' => $column->getNotnull() ? 'NO' : 'YES',
            'extra' => static::getExtra($column),
            'composite' => false,
        ];

        // Merge with options
        $columnArr = array_merge($columnArr, $column->getOptions());

        return $columnArr;
    }

    /**
     * @return string
     */
    protected static function getExtra($column)
    {
        $extra = '';

        $extra .= $column->getAutoincrement() ? 'auto_increment' : '';
        // todo: Add Extra stuff like mysql 'onUpdate' etc...

        return $extra;
    }
}
