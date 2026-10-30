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
        
        // Convert string type to Type object
        if (is_string($type)) {
            $typeObj = Type::getType($type);
            if (!$typeObj) {
                throw new \RuntimeException("Type {$type} not found");
            }
            $type = $typeObj;
        }
        
        $this->type = $type;
        $this->options = $options;
    }

    public static function make(array $column, ?string $tableName = null)
    {
        $name = Identifier::validate($column['name'], 'Column');
        $type = $column['type'];

        if (is_array($type)) {
            $type = $type['name'] ?? '';
        }

        $typeObj = Type::getType(trim($type));
        if (!$typeObj) {
            throw new \RuntimeException("Type {$type} not found");
        }

        $typeObj->tableName = $tableName;

        $options = array_diff_key($column, array_flip([
            'name', 'composite', 'oldName', 'type', 'type_name', 'null', 'nullable', 'generation',
        ]));

        // Normalize Laravel schema introspection keys into Voyager option names.
        if (!array_key_exists('notnull', $options)) {
            $options['notnull'] = array_key_exists('null', $column)
                ? strtoupper((string) $column['null']) === 'NO'
                : !($column['nullable'] ?? false);
        }

        if (array_key_exists('auto_increment', $column) && !array_key_exists('autoincrement', $options)) {
            $options['autoincrement'] = (bool) $column['auto_increment'];
        }

        unset($options['auto_increment']);

        if (isset($options['default']) && is_string($options['default'])) {
            $options['default'] = static::unquoteDefault($options['default']);
        }

        return new self($name, $typeObj, $options);
    }

    /**
     * SQLite reports string defaults including their SQL quotes.
     */
    protected static function unquoteDefault($default)
    {
        if (strlen($default) >= 2 && $default[0] === "'" && substr($default, -1) === "'") {
            return str_replace("''", "'", substr($default, 1, -1));
        }

        return $default;
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
        return (bool) ($this->options['autoincrement'] ?? false);
    }

    public function getNotnull()
    {
        return (bool) ($this->options['notnull'] ?? false);
    }

    public function getDefault()
    {
        return $this->options['default'] ?? null;
    }

    public function getLength()
    {
        return $this->options['length'] ?? null;
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
