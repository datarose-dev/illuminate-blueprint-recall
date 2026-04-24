<?php

namespace Datarose\BlueprintRecall;

use Datarose\BlueprintRecall\Column;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\ServiceProvider;

class BlueprintRecallServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerBlueprintColumnMacro();
    }

    private function registerBlueprintColumnMacro(): void
    {
        if (Blueprint::hasMacro('column')) {
            return;
        }

        Blueprint::macro('column', app(Column::class)());
    }
}
