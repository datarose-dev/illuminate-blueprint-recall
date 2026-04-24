<?php

declare(strict_types=1);

namespace Datarose\BlueprintRecall\Exceptions;

use Exception;

class SqlTypeConversionException extends Exception
{
    public function __construct(string $sqlType)
    {
        $message = sprintf('%s SQL-Type cannot be converted to a Laravel-Type.', $sqlType);
        parent::__construct($message);
    }

    public function render(): void
    {
        $this->getMessage();
    }
}
