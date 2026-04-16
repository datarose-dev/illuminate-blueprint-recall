<?php

namespace Datarose\BlueprintRecall\Exceptions;

use Exception;

class ColumnMacroAlreadyExistsException extends Exception
{
    public function __construct()
    {
        $message = "The 'column' macro already exists and cannot be created.";
        parent::__construct($message);
    }

    public function render()
    {
        $this->getMessage();
        return;
    }
}
