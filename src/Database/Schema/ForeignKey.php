<?php

namespace TCG\Voyager\Database\Schema;

class ForeignKey
{
    protected $name;
    protected $localTable;
    protected $localColumns;
    protected $foreignTable;
    protected $foreignColumns;
    protected $options;

    public function __construct($name, $localTable, $localColumns, $foreignTable, $foreignColumns, $options = [])
    {
        $this->name = $name;
        $this->localTable = $localTable;
        $this->localColumns = $localColumns;
        $this->foreignTable = $foreignTable;
        $this->foreignColumns = $foreignColumns;
        $this->options = $options;
    }

    public static function make(array $foreignKey)
    {
        $localTable = $foreignKey['localTable'] ?? null;
        $localColumns = $foreignKey['localColumns'] ?? [];
        $foreignTable = $foreignKey['foreignTable'] ?? '';
        $foreignColumns = $foreignKey['foreignColumns'] ?? [];
        $options = $foreignKey['options'] ?? [];

        // Set the name
        $name = isset($foreignKey['name']) ? trim($foreignKey['name']) : '';
        if (empty($name)) {
            $name = Index::createName($localColumns, 'foreign', $localTable);
        } else {
            $name = Identifier::validate($name, 'Foreign Key');
        }

        return new self($name, $localTable, $localColumns, $foreignTable, $foreignColumns, $options);
    }

    public function getName()
    {
        return $this->name;
    }

    public function getLocalTableName()
    {
        return $this->localTable;
    }

    public function getLocalColumns()
    {
        return $this->localColumns;
    }

    public function getForeignTableName()
    {
        return $this->foreignTable;
    }

    public function getForeignColumns()
    {
        return $this->foreignColumns;
    }

    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return array
     */
    public static function toArray($fk)
    {
        return [
            'name'           => $fk->getName(),
            'localTable'     => $fk->getLocalTableName(),
            'localColumns'   => $fk->getLocalColumns(),
            'foreignTable'   => $fk->getForeignTableName(),
            'foreignColumns' => $fk->getForeignColumns(),
            'options'        => $fk->getOptions(),
        ];
    }
}
