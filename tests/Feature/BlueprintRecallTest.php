<?php

use Datarose\BlueprintRecall\Exceptions\ColumnNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

it('registers the column macro', function (): void {
    expect(Blueprint::hasMacro('column'))->toBeTrue();
});

it('makes an existing string column nullable without redefining the column type', function (): void {
    Schema::create('users', function (Blueprint $table): void {
        $table->string('name', 100)->nullable(false);
    });

    Schema::table('users', function (Blueprint $table): void {
        $table->column('name')->nullable();
    });

    $column = collect(Schema::getColumns('users'))->firstWhere('name', 'name');

    expect($column)->not->toBeNull()
        ->and($column['nullable'])->toBeTrue();
});

it('removes nullable from an existing string column', function (): void {
    Schema::create('users', function (Blueprint $table): void {
        $table->string('name', 100)->nullable();
    });

    Schema::table('users', function (Blueprint $table): void {
        $table->column('name')->nullable(false);
    });

    $column = collect(Schema::getColumns('users'))->firstWhere('name', 'name');

    expect($column)->not->toBeNull()
        ->and($column['nullable'])->toBeFalse();
});

it('keeps the original string column type when changing nullable', function (): void {
    Schema::create('users', function (Blueprint $table): void {
        $table->string('email', 191);
    });

    Schema::table('users', function (Blueprint $table): void {
        $table->column('email')->nullable();
    });

    $column = collect(Schema::getColumns('users'))->firstWhere('name', 'email');

    expect($column)->not->toBeNull()
        ->and($column['type'])->toContain('varchar')
        ->and($column['nullable'])->toBeTrue();
});

it('preserves default values when changing nullable', function (): void {
    Schema::create('settings', function (Blueprint $table): void {
        $table->string('status', 32)->default('active');
    });

    Schema::table('settings', function (Blueprint $table): void {
        $table->column('status')->nullable();
    });

    $column = collect(Schema::getColumns('settings'))->firstWhere('name', 'status');

    expect($column)->not->toBeNull()
        ->and((string) $column['default'])->toContain('active')
        ->and($column['nullable'])->toBeTrue();
});

it('throws an exception when the column does not exist', function (): void {
    Schema::create('users', function (Blueprint $table): void {
        $table->id();
    });

    Schema::table('users', function (Blueprint $table): void {
        $table->column('missing_column')->nullable();
    });
})->throws(ColumnNotFoundException::class);
