<?php

namespace TCG\Voyager\Database;

use TCG\Voyager\Database\Schema\SchemaManager;
use TCG\Voyager\Database\Schema\Table;
use TCG\Voyager\Database\Types\Type;

class DatabaseUpdater
{
    protected $tableArr;
    protected $table;
    protected $originalTable;

    public function __construct(array $tableArr)
    {
        Type::registerCustomPlatformTypes();

        $this->table = Table::make($tableArr);
        $this->tableArr = $tableArr;
        $this->originalTable = SchemaManager::listTableDetails($tableArr['oldName']);
    }

    /**
     * Update the table.
     *
     * @return void
     */
    public static function update($table)
    {
        if (!is_array($table)) {
            $table = json_decode($table, true);
        }

        if (!SchemaManager::tableExists($table['oldName'])) {
            throw new \RuntimeException("Table {$table['oldName']} does not exist");
        }

        $updater = new self($table);

        $updater->updateTable();
    }

    /**
     * Updates the table.
     *
     * @return void
     */
    public function updateTable()
    {
        // Database update functionality is temporarily disabled for Laravel 12 compatibility
        // Doctrine DBAL integration has been removed from Laravel 12
        throw new \RuntimeException('Database update functionality is temporarily disabled for Laravel 12 compatibility. Doctrine DBAL integration has been removed from Laravel framework.');
    }

    /**
     * Get columns that were renamed.
     *
     * @return array
     */
    protected function getRenamedColumns()
    {
        return [];
    }

    /**
     * Get indexes that were renamed.
     *
     * @return array
     */
    protected function getRenamedIndexes()
    {
        return [];
    }
}
