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
    private readonly string $table;
    private string $column;
    private readonly Collection $columns;
    private readonly Collection $indexes;

    public function __construct(private readonly Blueprint $blueprint)
    {
        $this->table = $this->blueprint->getTable();
        $this->columns = collect(Schema::getColumns($this->table));
        $this->indexes = collect(Schema::getIndexes($this->table));
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
                'allowed' => explode(',', (string) Str::of($type)->match('/\((.+)\)/')->replace("'", '')->__toString()),
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
                'allowed' => explode(',', (string) Str::of($type)->match('/\((.+)\)/')->replace("'", '')->__toString()),
            ],

            default => [],
        };
    }

    /**
     * Apply column properties (nullable, default, unsigned, etc.).
     */
    private function applyColumnProperties(array $columnData): void
    {
        $this->definition->autoIncrement();

        $type = strtolower((string) ($columnData['type'] ?? ''));

        if (($columnData['unsigned'] ?? false) || str_contains($type, 'unsigned')) {
            $this->definition->unsigned();
        }

        if ($columnData['nullable'] ?? false) {
            $this->definition->nullable();
        }

        if (array_key_exists('default', $columnData) && ! is_null($columnData['default'])) {
            $default = $this->getColumnDefaultValue($columnData['type'] ?? 'string', $columnData['default']);
            $this->definition->default($default);
        }

        if (! is_null($columnData['comment'] ?? null)) {
            $this->definition->comment($columnData['comment']);
        }

        if ($this->isUnique()) {
            $this->definition->unique();
        }
    }

    private function getColumnDefaultValue(string $type, ?string $value)
    {
        $type = strtolower($type);

        if (is_null($value)) {
            throw new InvalidColumnDefaultValueException($this->column);
        }

        $default = trim($value, "'\"");

        return match (true) {
            str_contains($type, 'int') => (int) $default,
            str_contains($type, 'float'),
            str_contains($type, 'double'),
            str_contains($type, 'decimal') => (float) $default,
            str_contains($type, 'bool') => filter_var($default, FILTER_VALIDATE_BOOLEAN),
            str_contains($type, 'json') => json_decode($default, true) ?? '{}',
            str_contains($type, 'binary'),
            str_contains($type, 'blob') => base64_decode($default),
            default => $default,
        };
    }

    private function isUnique(): bool
    {
        return ColumnIndexInspector::isSingleColumnUnique($this->indexes, $this->column);
    }
}
