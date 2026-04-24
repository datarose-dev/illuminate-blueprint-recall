<?php

use Datarose\BlueprintRecall\Helpers\ColumnDefinitionBuilder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

function getTypeDetails(string $type): array
{
    $connection = DB::connection();

    if (! $connection->getSchemaGrammar()) {
        $connection->useDefaultSchemaGrammar();
    }

    $blueprint = new Blueprint($connection, 'temporary_table');
    $builder = new ColumnDefinitionBuilder($blueprint);

    return (new ReflectionClass($builder))
        ->getMethod('getTypeDetails')
        ->invoke($builder, $type);
}

it('parses varchar length correctly', function (): void {
    expect(getTypeDetails('varchar(191)'))
        ->toMatchArray([
            'length' => 191,
            'compressed' => false,
        ]);
});

it('parses compressed varchar correctly', function (): void {
    expect(getTypeDetails('varchar(191) compressed'))
        ->toMatchArray([
            'length' => 191,
            'compressed' => true,
        ]);
});

it('parses decimal precision correctly', function (): void {
    expect(getTypeDetails('decimal(10,2)'))
        ->toMatchArray([
            'total' => 10,
            'places' => 2,
        ]);
});

it('parses enum allowed values correctly', function (): void {
    expect(getTypeDetails("enum('draft','published')"))
        ->toMatchArray([
            'allowed' => ['draft', 'published'],
        ]);
});

it('returns an empty detail array for unsupported types', function (): void {
    expect(getTypeDetails('text'))->toBe([]);
});
