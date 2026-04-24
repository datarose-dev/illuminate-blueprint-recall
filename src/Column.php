<?php

namespace Datarose\BlueprintRecall;

use Datarose\BlueprintRecall\Helpers\ColumnDefinitionBuilder;

/**
 * @mixin \Illuminate\Database\Schema\Blueprint
 */
class Column
{
    public function __invoke()
    {
        /**
         * Select a existed column on the table.
         */
        return function (string $column): \Illuminate\Database\Schema\ColumnDefinition {
            /** @var \Illuminate\Database\Schema\Blueprint $this */
            return (new ColumnDefinitionBuilder($this))->get($column);
        };
    }
}
