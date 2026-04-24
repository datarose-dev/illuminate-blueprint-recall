<?php

declare(strict_types=1);

/**
 * @method \Illuminate\Database\Schema\ColumnDefinition column(string $column) Set column properties for change
 */
class Blueprint
{
    //
}

if (! class_exists(Blueprint::class)) {
    class_alias(Blueprint::class, \Illuminate\Database\Schema\Blueprint::class);
}

/**
 * @method $this autoIncrement(bool $value = true) Set INTEGER columns as auto-increment (primary key) or NON-auto-increment (when change column by illuminate-macros)
 * @method $this unsigned(bool $value = true) Set the INTEGER column as UNSIGNED (MySQL) or NON-UNSIGNED (when change column by illuminate-macros)
 */
class ColumnDefinition
{
    //
}

if (! class_exists(ColumnDefinition::class)) {
    class_alias(ColumnDefinition::class, \Illuminate\Database\Schema\ColumnDefinition::class);
}
