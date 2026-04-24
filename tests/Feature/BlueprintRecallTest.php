<?php

use Datarose\BlueprintRecall\Exceptions\ColumnNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Datarose\BlueprintRecall\Helpers\ColumnDefinitionBuilder;
use Illuminate\Support\Collection;
use ReflectionClass;

it('registers the column macro', function () {
    expect(Blueprint::hasMacro('column'))->toBeTrue();
});

it('makes an existing string column nullable without redefining the column type', function () {
    Schema::create('users', function (Blueprint $table) {
        $table->string('name', 100)->nullable(false);
    });

    Schema::table('users', function (Blueprint $table) {
        $table->column('name')->nullable();
    });

    $column = collect(Schema::getColumns('users'))->firstWhere('name', 'name');

    expect($column)->not->toBeNull()
        ->and($column['nullable'])->toBeTrue();
});

it('removes nullable from an existing string column', function () {
    Schema::create('users', function (Blueprint $table) {
        $table->string('name', 100)->nullable();
    });

    Schema::table('users', function (Blueprint $table) {
        $table->column('name')->nullable(false);
    });

    $column = collect(Schema::getColumns('users'))->firstWhere('name', 'name');

    expect($column)->not->toBeNull()
        ->and($column['nullable'])->toBeFalse();
});

it('keeps the original string length when changing nullable', function () {
    Schema::create('users', function (Blueprint $table) {
        $table->string('email', 191);
    });

    Schema::table('users', function (Blueprint $table) {
        $table->column('email')->nullable();
    });

    $column = collect(Schema::getColumns('users'))->firstWhere('name', 'email');

    expect($column)->not->toBeNull()
        ->and($column['type'])->toContain('varchar')
        ->and($column['nullable'])->toBeTrue();
});

it('parses varchar length correctly', function () {
    Schema::create('users', function (Blueprint $table) {
        $table->string('email', 191);
    });

    Schema::table('users', function (Blueprint $table) {
        $builder = new ColumnDefinitionBuilder($table);

        $result = (new ReflectionClass($builder))
            ->getMethod('getTypeDetails')
            ->invoke($builder, 'varchar(191)');

        expect($result['length'])->toBe(191);
    });
});

it('preserves default values when changing nullable', function () {
    Schema::create('settings', function (Blueprint $table) {
        $table->string('status', 32)->default('active');
    });

    Schema::table('settings', function (Blueprint $table) {
        $table->column('status')->nullable();
    });

    $column = collect(Schema::getColumns('settings'))->firstWhere('name', 'status');

    expect($column)->not->toBeNull()
        ->and((string) $column['default'])->toContain('active')
        ->and($column['nullable'])->toBeTrue();
});

it('throws an exception when the column does not exist', function () {
    Schema::create('users', function (Blueprint $table) {
        $table->id();
    });

    Schema::table('users', function (Blueprint $table) {
        $table->column('missing_column')->nullable();
    });
})->throws(ColumnNotFoundException::class);

it('detects a single-column unique index', function () {
    Schema::create('users', function (Blueprint $table) {
        $table->string('email');
    });

    Schema::table('users', function (Blueprint $table) {
        $builder = new ColumnDefinitionBuilder($table);

        $reflection = new ReflectionClass($builder);

        $columnProperty = $reflection->getProperty('column');
        $columnProperty->setValue($builder, 'email');

        $indexesProperty = $reflection->getProperty('indexes');
        $indexesProperty->setValue($builder, collect([
            [
                'name' => 'users_email_unique',
                'columns' => ['email'],
                'unique' => true,
            ],
        ]));

        $result = $reflection
            ->getMethod('isUnique')
            ->invoke($builder);

        expect($result)->toBeTrue();
    });
});

it('does not treat composite unique indexes as single-column unique indexes', function () {
    Schema::create('users', function (Blueprint $table) {
        $table->string('email');
        $table->string('tenant_id');
    });

    Schema::table('users', function (Blueprint $table) {
        $builder = new ColumnDefinitionBuilder($table);

        $reflection = new ReflectionClass($builder);

        $columnProperty = $reflection->getProperty('column');
        $columnProperty->setValue($builder, 'email');

        $indexesProperty = $reflection->getProperty('indexes');
        $indexesProperty->setValue($builder, collect([
            [
                'name' => 'users_email_tenant_id_unique',
                'columns' => ['email', 'tenant_id'],
                'unique' => true,
            ],
        ]));

        $result = $reflection
            ->getMethod('isUnique')
            ->invoke($builder);

        expect($result)->toBeFalse();
    });
});

it('does not treat normal indexes as unique indexes', function () {
    Schema::create('users', function (Blueprint $table) {
        $table->string('email');
    });

    Schema::table('users', function (Blueprint $table) {
        $builder = new ColumnDefinitionBuilder($table);

        $reflection = new ReflectionClass($builder);

        $columnProperty = $reflection->getProperty('column');
        $columnProperty->setValue($builder, 'email');

        $indexesProperty = $reflection->getProperty('indexes');
        $indexesProperty->setValue($builder, collect([
            [
                'name' => 'users_email_index',
                'columns' => ['email'],
                'unique' => false,
            ],
        ]));

        $result = $reflection
            ->getMethod('isUnique')
            ->invoke($builder);

        expect($result)->toBeFalse();
    });
});
