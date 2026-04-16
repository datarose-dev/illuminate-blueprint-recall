<?php

namespace Datarose\BlueprintRecall\Helpers;

use Datarose\BlueprintRecall\Enums\BlueprintType;
use Datarose\BlueprintRecall\Exceptions\ColumnNotFoundException;
use Datarose\BlueprintRecall\Exceptions\InvalidColumnDefaultValueException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Support\Collection;
use Schema;
use Str;

class ColumnDefinitionBuilder
{
    private ColumnDefinition $definition;
    private string $table;
    private string $column;
    private Collection $columns;

    public function __construct(private Blueprint $blueprint)
    {
        $this->table = $this->blueprint->getTable();
        $this->columns = collect(Schema::getColumns($this->table));
    }

    public function get(string $column): ColumnDefinition
    {
        $this->column = $column;

        $columnData = $this->getColumnData();
        if (! $columnData) {
            throw new ColumnNotFoundException($this->table, $column);
        }

        $this->definition = $this->applyColumn($columnData);
        $this->applyColumnProperties($columnData);

        return $this->definition->change();
    }

    /**
     * Get the column data from the database.
     */
    private function getColumnData(): ?array
    {
        return $this->columns->firstWhere('name', $this->column);
    }

    /**
     * Get column definition
     */
    private function applyColumn(array $column): ColumnDefinition
    {
        return $this->blueprint->addColumn($this->getLaravelTypeName($column['type_name']), $column['name'], $this->getTypeDetails($column['type']));
    }

    /**
     * Get name of Laravel Type
     */
    private function getLaravelTypeName(string $type): string
    {
        return BlueprintType::fromSqlType($type)->value;
    }

    /**
     * Get details of type
     */
    private function getTypeDetails(string $type): array
    {
        // Parse type details with match
        return match (true) {
            Str::startsWith($type, 'varchar') ||
            Str::startsWith($type, 'char') => [
                'length' => (int) Str::of($type)->match('/\((\d+)\)/')->toString() ?: null,
                'compressed' => Str::of($type)->lower()->contains('compressed'),
            ],

            Str::startsWith($type, 'binary') => [
                'fixed' => true,
            ],

            Str::startsWith($type, 'tinyint') ||
            Str::startsWith($type, 'smallint') ||
            Str::startsWith($type, 'mediumint') ||
            Str::startsWith($type, 'bigint') ||
            Str::startsWith($type, 'integer') => [
                'zerofill' => Str::of($type)->lower()->contains('zerofill'),
            ],

            Str::startsWith($type, 'float') ||
            Str::startsWith($type, 'double') ||
            Str::startsWith($type, 'real') => [
                'precision' => (int) Str::of($type)->match('/\((\d+)\)/')->toString(),
            ],

            Str::startsWith($type, 'decimal') ||
            Str::startsWith($type, 'numeric') => [
                'total' => (int) Str::of($type)->match('/\((\d+),\d+\)/')->toString(),
                'places' => (int) Str::of($type)->match('/\(\d+,(\d+)\)/')->toString(),
            ],

            Str::startsWith($type, 'datetime') ||
            Str::startsWith($type, 'timestamp') => [
                'precision' => (int) Str::of($type)->match('/\((\d+)\)/')->toString(),
            ],

            Str::startsWith($type, 'enum') => [
                'allowed' => explode(',', Str::of($type)->match('/\((.+)\)/')->replace("'", '')->__toString()),
            ],

            Str::startsWith($type, 'geometry') ||
            Str::startsWith($type, 'geography') => [
                'subtype' => Str::of($type)->after(':')->before('(')->__toString() ?: null,
                'srid' => (int) Str::of($type)->match('/srid=(\d+)/i')->toString(),
            ],

            Str::startsWith($type, 'computed') => [
                'expression' => true,
            ],

            Str::startsWith($type, 'vector') => [
                'dimensions' => (int) Str::of($type)->match('/\((\d+)\)/')->toString(),
            ],

            Str::startsWith($type, 'bit') => [
                'length' => (int) Str::of($type)->match('/\((\d+)\)/')->toString() ?: null,
            ],

            Str::startsWith($type, 'set') => [
                'allowed' => explode(',', Str::of($type)->match('/\((.+)\)/')->replace("'", '')->__toString()),
            ],

            default => [],
        };
    }

    /**
     * Apply column properties (nullable, default, unsigned, etc.).
     */
    private function applyColumnProperties(array $columnData): void
    {
        $this->definition->autoIncrement($columnData['auto_increment']);

        if ($columnData['unsigned']) {
            $this->definition->unsigned();
        }

        if ($columnData['nullable']) {
            $this->definition->nullable();
        }

        if (! is_null($columnData['default'])) {
            $default = $this->getColumnDefaultValue($columnData['type'], $columnData['default']);
            $this->definition->default($default);
        }

        if (! is_null($columnData['comment'])) {
            $this->definition->comment($columnData['comment']);
        }
    }

    private function getColumnDefaultValue(string $type, ?string $value)
    {
        $type = strtolower($column['type'] ?? 'string');

        if (is_null($value)) {
            throw new InvalidColumnDefaultValueException($this->column);
        }

        $default = trim($value, "'\"");

        return match ($type) {
            'int', 'integer', 'bigint', 'smallint', 'tinyint' => (int) $default,
            'float', 'double', 'decimal' => (float) $default,
            'boolean', 'bool' => filter_var($default, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($default, true) ?? '{}',
            'binary', 'varbinary', 'blob' => base64_decode($default),
            default => $default,
        };
    }
}
