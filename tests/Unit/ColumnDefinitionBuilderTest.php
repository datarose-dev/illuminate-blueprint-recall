<?php

declare(strict_types=1);

use Datarose\BlueprintRecall\Helpers\ColumnDefinitionBuilder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

it('parses varchar length correctly', function (): void {
    Schema::create('users', function (Blueprint $table): void {
        $table->string('email', 191);
    });

    Schema::table('users', function (Blueprint $table): void {
        $builder = new ColumnDefinitionBuilder($table);

        $result = (new ReflectionClass($builder))
            ->getMethod('getTypeDetails')
            ->invoke($builder, 'varchar(191)');

        expect($result['length'])->toBe(191);
    });
});
