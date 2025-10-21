<?php

namespace TCG\Voyager\Database\Schema;

use Illuminate\Support\Facades\Schema as LaravelSchema;
use Illuminate\Support\Facades\DB;
use TCG\Voyager\Database\Types\Type;

abstract class SchemaManager
{
    public static function __callStatic($method, $args)
    {
        // Redirect to Laravel's Schema facade for basic operations
        if (method_exists(LaravelSchema::class, $method)) {
            return LaravelSchema::$method(...$args);
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
        
        // Get tables only from current database
        $currentDb = DB::getDatabaseName();
        $tablesFromDb = DB::select("
            SELECT TABLE_NAME as name 
            FROM information_schema.tables 
            WHERE table_schema = ? 
            AND table_type = 'BASE TABLE'
            ORDER BY TABLE_NAME
        ", [$currentDb]);

        foreach ($tablesFromDb as $tableInfo) {
            $tableName = $tableInfo->name;
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

        // Convert Laravel schema arrays to Voyager objects
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

            // Set the indexes and key
            $columnArr['indexes'] = [];
            $columnArr['key'] = null;
            
            if ($indexes = $table->getColumnsIndexes($columnArr['name'], true)) {
                // Convert indexes to Array
                $columnArr['indexes'] = [];
                foreach ($indexes as $name => $index) {
                    $columnArr['indexes'][$name] = Index::toArray($index);
                }

                // If there are multiple indexes for the column
                // the Key will be one with highest priority
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
        $tableNames = [];
        
        // Get tables only from current database
        $currentDb = DB::getDatabaseName();
        $tablesFromDb = DB::select("
            SELECT TABLE_NAME as name 
            FROM information_schema.tables 
            WHERE table_schema = ? 
            AND table_type = 'BASE TABLE'
            ORDER BY TABLE_NAME
        ", [$currentDb]);

        foreach ($tablesFromDb as $tableInfo) {
            $tableNames[] = $tableInfo->name;
        }

        return $tableNames;
    }

    public static function getDoctrineTable($table)
    {
        throw new \RuntimeException('Doctrine tables are not supported in Laravel 12. Use Laravel Schema methods instead.');
    }

    public static function getDoctrineColumn($table, $column)
    {
        throw new \RuntimeException('Doctrine columns are not supported in Laravel 12. Use Laravel Schema methods instead.');
    }

    public static function getDatabasePlatformName()
    {
        $driver = DB::connection()->getDriverName();
        
        // Map Laravel driver names to platform names
        $platformMap = [
            'mysql' => 'mysql',
            'pgsql' => 'postgresql', 
            'sqlite' => 'sqlite',
            'sqlsrv' => 'mssql',
        ];
        
        return $platformMap[$driver] ?? $driver;
    }
}
