<?php

namespace TCG\Voyager\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema as LaravelSchema;
use TCG\Voyager\Database\Schema\Column;
use TCG\Voyager\Database\Schema\SchemaException;
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
            throw SchemaException::tableDoesNotExist($table['oldName']);
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
        $this->renameTableIfNeeded();
        $this->renameColumns();

        $this->dropIndexes();
        $this->dropColumns();
        $this->addColumns();
        $this->changeColumns();
        $this->addIndexes();
    }

    protected function renameTableIfNeeded()
    {
        $newName = $this->table->getName();

        if ($newName === $this->originalTable->getName()) {
            return;
        }

        if (SchemaManager::tableExists($newName)) {
            throw SchemaException::tableAlreadyExists($newName);
        }

        SchemaManager::renameTable($this->originalTable->getName(), $newName);

        $this->tableArr['oldName'] = $newName;
        $this->originalTable = SchemaManager::listTableDetails($newName);
    }

    protected function renameColumns()
    {
        $renamed = [];

        foreach ($this->tableArr['columns'] as $column) {
            $oldName = $column['oldName'] ?? $column['name'];

            if ($oldName != $column['name'] && $this->originalTable->hasColumn($oldName)) {
                $renamed[$oldName] = $column['name'];
            }
        }

        if (empty($renamed)) {
            return;
        }

        LaravelSchema::table($this->originalTable->getName(), function (Blueprint $blueprint) use ($renamed) {
            foreach ($renamed as $oldName => $newName) {
                $blueprint->renameColumn($oldName, $newName);
            }
        });

        $this->rewriteColumnReferences($renamed);
        $this->refreshOriginalTable();
    }

    protected function rewriteColumnReferences(array $renamed)
    {
        foreach ($this->tableArr['indexes'] as &$index) {
            $index['columns'] = array_map(fn ($column) => $renamed[$column] ?? $column, (array) $index['columns']);
        }
        unset($index);

        foreach ($this->tableArr['foreignKeys'] as &$foreignKey) {
            $foreignKey['localColumns'] = array_map(fn ($column) => $renamed[$column] ?? $column, (array) $foreignKey['localColumns']);
        }
        unset($foreignKey);

        $this->table = Table::make($this->tableArr);
    }

    protected function dropColumns()
    {
        $dropped = [];

        foreach ($this->originalTable->getColumns() as $name => $column) {
            if (!$this->table->hasColumn($name)) {
                $dropped[] = $name;
            }
        }

        if (empty($dropped)) {
            return;
        }

        LaravelSchema::table($this->originalTable->getName(), function (Blueprint $blueprint) use ($dropped) {
            $blueprint->dropColumn($dropped);
        });

        $this->refreshOriginalTable();
    }

    protected function addColumns()
    {
        $added = [];

        foreach ($this->table->getColumns() as $name => $column) {
            if (!$this->originalTable->hasColumn($name)) {
                $added[] = $column;
            }
        }

        if (empty($added)) {
            return;
        }

        LaravelSchema::table($this->originalTable->getName(), function (Blueprint $blueprint) use ($added) {
            foreach ($added as $column) {
                SchemaManager::addColumnToBlueprint($blueprint, $column);
            }
        });

        $this->refreshOriginalTable();
    }

    protected function changeColumns()
    {
        $changed = [];

        foreach ($this->table->getColumns() as $name => $column) {
            if (!$this->originalTable->hasColumn($name)) {
                continue;
            }

            if ($this->columnSignature($column) !== $this->columnSignature($this->originalTable->getColumn($name))) {
                $changed[] = $column;
            }
        }

        if (empty($changed)) {
            return;
        }

        LaravelSchema::table($this->originalTable->getName(), function (Blueprint $blueprint) use ($changed) {
            foreach ($changed as $column) {
                SchemaManager::addColumnToBlueprint($blueprint, $column)->change();
            }
        });

        $this->refreshOriginalTable();
    }

    protected function dropIndexes()
    {
        $original = $this->originalTable->getIndexes();
        $current = $this->table->getIndexes();
        $columnNames = array_keys($this->table->getColumns());

        $dropped = [];

        foreach ($original as $name => $index) {
            if ($index->isPrimary()) {
                continue;
            }

            $referencesMissingColumn = array_diff($index->getColumns(), $columnNames) !== [];
            $changed = !isset($current[$name]) || $this->indexSignature($index) !== $this->indexSignature($current[$name]);

            if ($referencesMissingColumn || $changed) {
                $dropped[] = $index;
            }
        }

        if (empty($dropped)) {
            return;
        }

        LaravelSchema::table($this->originalTable->getName(), function (Blueprint $blueprint) use ($dropped) {
            foreach ($dropped as $index) {
                $index->isUnique()
                    ? $blueprint->dropUnique($index->getName())
                    : $blueprint->dropIndex($index->getName());
            }
        });

        $this->refreshOriginalTable();
    }

    protected function addIndexes()
    {
        $current = $this->table->getIndexes();
        $original = $this->originalTable->getIndexes();
        $columnNames = array_keys($this->table->getColumns());

        $added = [];

        foreach ($current as $name => $index) {
            if (array_diff($index->getColumns(), $columnNames) !== []) {
                continue;
            }

            if ($index->isPrimary()) {
                if (!isset($original[$name])) {
                    $added[] = $index;
                }

                continue;
            }

            if (!isset($original[$name]) || $this->indexSignature($index) !== $this->indexSignature($original[$name])) {
                $added[] = $index;
            }
        }

        if (empty($added)) {
            return;
        }

        LaravelSchema::table($this->originalTable->getName(), function (Blueprint $blueprint) use ($added) {
            foreach ($added as $index) {
                SchemaManager::addIndexToBlueprint($blueprint, $index);
            }
        });
    }

    protected function refreshOriginalTable()
    {
        $this->originalTable = SchemaManager::listTableDetails($this->originalTable->getName());
    }

    protected function columnSignature(Column $column)
    {
        $options = $column->getOptions();

        return [
            'type'        => strtolower($column->getType()->getName()),
            'notnull'     => $column->getNotnull(),
            'default'     => $this->normalizeScalar($column->getDefault()),
            'unsigned'    => !empty($options['unsigned']),
            'autoincrement' => $column->getAutoincrement(),
            'length'      => $this->normalizeInt($options['length'] ?? null),
            'precision'   => $this->normalizeInt($options['precision'] ?? null),
            'scale'       => $this->normalizeInt($options['scale'] ?? null),
            'comment'     => (string) ($options['comment'] ?? ''),
        ];
    }

    protected function indexSignature($index)
    {
        return [
            'columns' => array_values($index->getColumns()),
            'unique'  => $index->isUnique(),
            'primary' => $index->isPrimary(),
        ];
    }

    protected function normalizeScalar($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) $value;
    }

    protected function normalizeInt($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
