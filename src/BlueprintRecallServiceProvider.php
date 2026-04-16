<?php

namespace Datarose\BlueprintPreserve;

use Datarose\BlueprintRecall\Column;
use Datarose\BlueprintRecall\Exceptions\ColumnMacroAlreadyExistsException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\ServiceProvider;

class BlueprintRecallServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->registerBlueprintColumnMacro();
    }

    private function registerBlueprintColumnMacro()
    {
        $this->ensureColumnMacroDoesNotExist();

        Blueprint::macro('column', Column::class);
    }

    private function ensureColumnMacroDoesNotExist()
    {
        if (Blueprint::hasMacro('column')) {
            throw new ColumnMacroAlreadyExistsException();
        }
    }
}
