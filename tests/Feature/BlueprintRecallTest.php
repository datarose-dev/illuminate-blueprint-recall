<?php

use Datarose\BlueprintRecall\Exceptions\ColumnNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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

it('keeps the original string column type when changing nullable', function () {
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
