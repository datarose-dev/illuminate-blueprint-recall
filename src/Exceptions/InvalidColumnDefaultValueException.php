<?php

declare(strict_types=1);

namespace Datarose\BlueprintRecall\Exceptions;

use Exception;

class InvalidColumnDefaultValueException extends Exception
{
    public function __construct(string $column)
    {
        $message = "The column '{$column}' does not have a valid default value.";
        parent::__construct($message);
    }

    public function render(): void
    {
        $this->getMessage();
    }
}
