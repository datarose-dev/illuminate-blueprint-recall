<?php

use Datarose\BlueprintRecall\Helpers\ColumnDefinitionBuilder;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

/**
 * Laravel 11/12 use the legacy Blueprint constructor signature:
 * new Blueprint(string $table, ?Closure $callback = null, string $prefix = '').
 *
 * Laravel 13+ requires the connection-aware signature:
 * new Blueprint(Connection $connection, string $table, ?callable $callback = null).
 *
 * The branch above handles the new signature. This fallback is intentionally
 * kept for older Laravel versions supported by the package.
 */
function makeBlueprint(string $table = 'temporary_table'): Blueprint
{
    $connection = DB::connection();

    $firstType = (new ReflectionMethod(Blueprint::class, '__construct'))
        ->getParameters()[0]
        ->getType();

    if ($firstType instanceof ReflectionNamedType && $firstType->getName() === Connection::class) {
        return new Blueprint($connection, $table);
    }

    // phpcs:ignore
    return new Blueprint($table);
}

function getTypeDetails(string $type): array
{
    $connection = DB::connection();

    if (! $connection->getSchemaGrammar()) {
        $connection->useDefaultSchemaGrammar();
    }

    $blueprint = makeBlueprint('temporary_table');
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
