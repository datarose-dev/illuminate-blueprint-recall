<?php

namespace Datarose\BlueprintRecall\Helpers;

use Illuminate\Support\Collection;

class ColumnIndexInspector
{
    public static function isSingleColumnUnique(Collection $indexes, string $column): bool
    {
        return $indexes->contains(function (array $index) use ($column): bool {
            $columns = $index['columns'] ?? [];

            return ($index['unique'] ?? false)
                && count($columns) === 1
                && ($columns[0] ?? null) === $column;
        });
    }
}
