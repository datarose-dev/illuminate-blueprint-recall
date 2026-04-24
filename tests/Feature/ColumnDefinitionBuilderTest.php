<?php

use Datarose\BlueprintRecall\Helpers\ColumnDefinitionBuilder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
