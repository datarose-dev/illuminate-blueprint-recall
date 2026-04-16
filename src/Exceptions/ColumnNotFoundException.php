<?php

namespace Datarose\BlueprintRecall\Exceptions;

use Exception;

class ColumnNotFoundException extends Exception
{
    public function __construct(string $table, string $column)
    {
        $message = "The column '{$column}' was not found in the '{$table}' table.";
        parent::__construct($message);
    }

    public function render()
    {
        $this->getMessage();
        return;
    }
}
