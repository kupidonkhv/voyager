<?php

namespace TCG\Voyager\Database\Schema;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema as LaravelSchema;
use Illuminate\Support\Facades\DB;
use TCG\Voyager\Database\Types\Type;

abstract class SchemaManager
{
    public static function __callStatic($method, $args)
    {
        $builder = DB::connection()->getSchemaBuilder();

        if (method_exists($builder, $method)) {
            return $builder->$method(...$args);
        }

        throw new \BadMethodCallException("Method {$method} not found");
    }

    public static function tableExists($table)
    {
        return LaravelSchema::hasTable($table);
    }

    public static function listTables()
    {
        $tables = [];

        foreach (LaravelSchema::getTables() as $tableInfo) {
            $tableName = $tableInfo['name'];
            $tables[$tableName] = static::listTableDetails($tableName);
        }

        return $tables;
    }

    public static function listTableDetails($tableName)
    {
        Type::registerCustomPlatformTypes();

        $columns = LaravelSchema::getColumns($tableName);
        $indexes = LaravelSchema::getIndexes($tableName);
        $foreignKeys = LaravelSchema::getForeignKeys($tableName);

        $columnObjects = [];
        foreach ($columns as $columnArr) {
            $columnObjects[$columnArr['name']] = Column::make($columnArr, $tableName);
        }

        $indexObjects = [];
        foreach ($indexes as $indexArr) {
            $indexObjects[$indexArr['name']] = Index::make($indexArr);
        }

        $foreignKeyObjects = [];
        foreach ($foreignKeys as $fkArr) {
            $foreignKeyObjects[$fkArr['name']] = ForeignKey::make($fkArr);
        }

        return new Table($tableName, $columnObjects, $indexObjects, $foreignKeyObjects, []);
    }

    public static function describeTable($tableName)
    {
        Type::registerCustomPlatformTypes();

        $table = static::listTableDetails($tableName);

        return collect($table->getColumns())->map(function ($column) use ($table) {
            $columnArr = Column::toArray($column);

            $columnArr['field'] = $columnArr['name'];
            $columnArr['type'] = $columnArr['type']['name'];

            $columnArr['indexes'] = [];
            $columnArr['key'] = null;

            if ($indexes = $table->getColumnsIndexes($columnArr['name'], true)) {
                foreach ($indexes as $name => $index) {
                    $columnArr['indexes'][$name] = Index::toArray($index);
                }

                if (!empty($columnArr['indexes'])) {
                    $indexType = array_values($columnArr['indexes'])[0]['type'];
                    $columnArr['key'] = substr($indexType, 0, 3);
                }
            }

            return $columnArr;
        });
    }

    public static function listTableColumnNames($tableName)
    {
        Type::registerCustomPlatformTypes();

        $columnNames = [];

        foreach (LaravelSchema::getColumns($tableName) as $column) {
            $columnNames[] = $column['name'];
        }

        return $columnNames;
    }

    public static function listTableNames()
    {
        return array_map(
            fn (array $table) => $table['name'],
            LaravelSchema::getTables()
        );
    }

    /**
     * @param Table|array $table
     */
    public static function createTable($table)
    {
        if (is_array($table)) {
            $table = Table::make($table);
        }

        $name = $table->getName();

        if (static::tableExists($name)) {
            throw new \RuntimeException("table {$name} already exists");
        }

        $columns = $table->getColumns();
        $indexes = $table->getIndexes();
        $foreignKeys = $table->getForeignKeys();

        [$autoIncrementColumn, $primaryIndex] = static::detectAutoIncrementPrimary($columns, $indexes);

        LaravelSchema::create($name, function (Blueprint $blueprint) use ($columns, $indexes, $foreignKeys, $autoIncrementColumn, $primaryIndex) {
            foreach ($columns as $column) {
                if ($autoIncrementColumn && $column === $autoIncrementColumn) {
                    static::addIncrementingColumn($blueprint, $column);
                    continue;
                }

                static::addColumnToBlueprint($blueprint, $column);
            }

            foreach ($indexes as $index) {
                if ($primaryIndex && $index === $primaryIndex) {
                    continue;
                }

                static::addIndexToBlueprint($blueprint, $index);
            }

            foreach ($foreignKeys as $foreignKey) {
                static::addForeignKeyToBlueprint($blueprint, $foreignKey);
            }
        });
    }

    public static function dropTable($table)
    {
        LaravelSchema::drop($table);
    }

    public static function renameTable($oldName, $newName)
    {
        LaravelSchema::rename($oldName, $newName);
    }

    /**
     * An auto-incrementing primary key is declared by the column itself;
     * adding a separate primary key constraint would duplicate it.
     */
    public static function detectAutoIncrementPrimary($columns, $indexes)
    {
        $autoIncrementColumn = null;

        foreach ($columns as $column) {
            if ($column->getAutoincrement()) {
                $autoIncrementColumn = $column;
                break;
            }
        }

        if (!$autoIncrementColumn) {
            return [null, null];
        }

        foreach ($indexes as $index) {
            $indexColumns = $index->getColumns();

            if ($index->isPrimary() && count($indexColumns) === 1 && $indexColumns[0] === $autoIncrementColumn->getName()) {
                return [$autoIncrementColumn, $index];
            }
        }

        return [null, null];
    }

    public static function addIncrementingColumn(Blueprint $blueprint, Column $column)
    {
        $type = strtolower($column->getType()->getName());

        if ($type === 'bigint') {
            return $blueprint->bigIncrements($column->getName());
        }

        return $blueprint->increments($column->getName());
    }

    /**
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public static function addColumnToBlueprint(Blueprint $blueprint, Column $column)
    {
        $name = $column->getName();
        $type = strtolower($column->getType()->getName());
        $options = $column->getOptions();

        $length = static::intOption($options, 'length');
        $precision = static::intOption($options, 'precision') ?? $length;
        $scale = static::intOption($options, 'scale');
        $unsigned = !empty($options['unsigned']);
        $autoIncrement = $column->getAutoincrement();

        switch ($type) {
            case 'bigint':
                $definition = $blueprint->bigInteger($name, $autoIncrement, $unsigned);
                break;
            case 'tinyint':
                $definition = $blueprint->tinyInteger($name, $autoIncrement, $unsigned);
                break;
            case 'smallint':
                $definition = $blueprint->smallInteger($name, $autoIncrement, $unsigned);
                break;
            case 'mediumint':
                $definition = $blueprint->mediumInteger($name, $autoIncrement, $unsigned);
                break;
            case 'int':
            case 'integer':
                $definition = $blueprint->integer($name, $autoIncrement, $unsigned);
                break;
            case 'decimal':
            case 'numeric':
            case 'money':
                $definition = $blueprint->decimal($name, $precision ?? 8, $scale ?? 2);
                break;
            case 'float':
                $definition = $blueprint->float($name, $precision ?? 53);
                break;
            case 'double':
            case 'double precision':
            case 'real':
                $definition = $blueprint->double($name);
                break;
            case 'boolean':
            case 'bool':
                $definition = $blueprint->boolean($name);
                break;
            case 'char':
            case 'character':
                $definition = $blueprint->char($name, $length ?? 255);
                break;
            case 'string':
            case 'varchar':
            case 'character varying':
                $definition = $blueprint->string($name, $length ?? 255);
                break;
            case 'tinytext':
                $definition = $blueprint->tinyText($name);
                break;
            case 'mediumtext':
                $definition = $blueprint->mediumText($name);
                break;
            case 'longtext':
                $definition = $blueprint->longText($name);
                break;
            case 'text':
                $definition = $blueprint->text($name);
                break;
            case 'json':
            case 'jsonb':
            case 'json_array':
            case 'simple_array':
            case 'array':
                $definition = $blueprint->json($name);
                break;
            case 'uuid':
            case 'guid':
                $definition = $blueprint->uuid($name);
                break;
            case 'date':
                $definition = $blueprint->date($name);
                break;
            case 'datetime':
            case 'datetimetz':
                $definition = $blueprint->dateTime($name, $precision ?? 0);
                break;
            case 'timestamp':
            case 'timestamptz':
                $definition = $blueprint->timestamp($name, $precision ?? 0);
                break;
            case 'time':
            case 'timetz':
                $definition = $blueprint->time($name, $precision ?? 0);
                break;
            case 'year':
                $definition = $blueprint->year($name);
                break;
            case 'binary':
            case 'varbinary':
            case 'blob':
            case 'tinyblob':
            case 'mediumblob':
            case 'longblob':
            case 'bytea':
            case 'bit':
            case 'bit varying':
                $definition = $blueprint->binary($name);
                break;
            case 'geometry':
            case 'point':
            case 'linestring':
            case 'polygon':
            case 'multipoint':
            case 'multilinestring':
            case 'multipolygon':
            case 'geometrycollection':
                $definition = method_exists($blueprint, 'geometry')
                    ? $blueprint->geometry($name)
                    : $blueprint->text($name);
                break;
            default:
                $definition = $blueprint->string($name, $length ?? 255);
                break;
        }

        return static::applyColumnModifiers($definition, $column);
    }

    public static function applyColumnModifiers($definition, Column $column)
    {
        $options = $column->getOptions();

        if (!$column->getNotnull()) {
            $definition->nullable();
        }

        $default = $column->getDefault();
        if ($default !== null && $default !== '') {
            $definition->default($default);
        }

        if (!empty($options['comment'])) {
            $definition->comment($options['comment']);
        }

        if (!empty($options['collation']) && method_exists($definition, 'collation')) {
            $definition->collation($options['collation']);
        }

        return $definition;
    }

    public static function addIndexToBlueprint(Blueprint $blueprint, $index)
    {
        $columns = $index->getColumns();
        $name = $index->getName();

        if ($index->isPrimary()) {
            return $blueprint->primary($columns, $name === 'primary' ? null : $name);
        }

        if ($index->isUnique()) {
            return $blueprint->unique($columns, $name);
        }

        return $blueprint->index($columns, $name);
    }

    public static function addForeignKeyToBlueprint(Blueprint $blueprint, ForeignKey $foreignKey)
    {
        $definition = $blueprint->foreign($foreignKey->getLocalColumns(), $foreignKey->getName())
            ->references($foreignKey->getForeignColumns())
            ->on($foreignKey->getForeignTableName());

        $options = $foreignKey->getOptions();

        if (isset($options['onDelete'])) {
            $definition->onDelete($options['onDelete']);
        }

        if (isset($options['onUpdate'])) {
            $definition->onUpdate($options['onUpdate']);
        }

        return $definition;
    }

    protected static function intOption(array $options, $key)
    {
        if (!isset($options[$key]) || $options[$key] === '') {
            return null;
        }

        return (int) $options[$key];
    }

    public static function getDoctrineTable($table)
    {
        throw new \RuntimeException('Doctrine tables are not supported. Use Laravel Schema methods instead.');
    }

    public static function getDoctrineColumn($table, $column)
    {
        throw new \RuntimeException('Doctrine columns are not supported. Use Laravel Schema methods instead.');
    }

    public static function getDatabasePlatformName()
    {
        $driver = DB::connection()->getDriverName();

        $platformMap = [
            'mysql' => 'mysql',
            'mariadb' => 'mysql',
            'pgsql' => 'postgresql',
            'sqlite' => 'sqlite',
            'sqlsrv' => 'mssql',
        ];

        return $platformMap[$driver] ?? $driver;
    }
}
