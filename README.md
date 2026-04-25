# datarose/illuminate-blueprint-recall

`datarose/illuminate-blueprint-recall` is an unofficial Laravel package that restores column attribute persistence when changing existing columns in migrations.

It adds a `column()` macro to Laravel's `Blueprint`, allowing you to update only the attributes you actually want to change.

```php
Schema::table('users', function (Blueprint $table) {
    $table->column('votes')->nullable();
});
````

Instead of repeating the full column definition:

```php
Schema::table('users', function (Blueprint $table) {
    $table->integer('votes')
        ->unsigned()
        ->default(1)
        ->comment('The vote count')
        ->nullable()
        ->change();
});
```

* [Why this exists](#why-this-exists)
* [Get started](#get-started)
* [Usage](#usage)
* [How it works](#how-it-works)
* [IDE support](#ide-support)
* [Limitations](#limitations)
* [Testing](#testing)
* [Contributing](#contributing)

## Why this exists

Starting with Laravel 11, modifying a column requires redefining all existing attributes of that column. If an attribute is not explicitly included in the migration, Laravel may drop it.

For example, given this original column:

```php
Schema::create('users', function (Blueprint $table) {
    $table->integer('votes')
        ->unsigned()
        ->default(1)
        ->comment('The vote count');
});
```

This Laravel 11+ migration only makes the column nullable, but may drop the previous `unsigned`, `default`, and `comment` attributes:

```php
Schema::table('users', function (Blueprint $table) {
    $table->integer('votes')->nullable()->change();
});
```

Laravel now expects the full definition:

```php
Schema::table('users', function (Blueprint $table) {
    $table->integer('votes')
        ->unsigned()
        ->default(1)
        ->comment('The vote count')
        ->nullable()
        ->change();
});
```

`datarose/illuminate-blueprint-recall` avoids this repetition by reading the current column definition from the database and applying the existing attributes automatically.

## Get started

Install the package with Composer:

```sh
composer require datarose/illuminate-blueprint-recall
```

Laravel package auto-discovery will register the service provider automatically.

If you do not use auto-discovery, register the provider manually:

```php
Datarose\BlueprintRecall\BlueprintRecallServiceProvider::class
```

## Usage

Use `column()` when you want to change an existing column while keeping its current attributes.

### Make a column nullable

```php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

Schema::table('users', function (Blueprint $table) {
    $table->column('votes')->nullable();
});
```

The package recalls the existing column type and attributes, then applies only the new change.

### Remove nullable

```php
Schema::table('users', function (Blueprint $table) {
    $table->column('votes')->nullable(false);
});
```

### Change multiple attributes

```php
Schema::table('users', function (Blueprint $table) {
    $table->column('votes')
        ->nullable()
        ->default(0);
});
```

### Keep the original type

You do not need to specify whether the column is an integer, string, text, decimal, or another supported type.

```php
Schema::table('users', function (Blueprint $table) {
    $table->column('email')->nullable();
});
```

The package resolves the existing database column and builds the matching Laravel column definition before calling `change()`.

## How it works

The package registers a `column()` macro on Laravel's `Illuminate\Database\Schema\Blueprint`.

When called, it:

1. Reads the current table name from the active `Blueprint`.
2. Loads the table columns from the database schema.
3. Finds the requested column.
4. Converts the database column type back to a Laravel Blueprint type.
5. Re-applies existing attributes such as:
   * nullable
   * default value
   * unsigned
   * auto increment
   * comment
6. Returns the column definition with `change()` already applied.

This lets your migration focus on the change you actually want to make.

```php
$table->column('votes')->nullable();
```

## IDE support

The package includes an optional IDE helper file for macro autocomplete.

It documents the added `column()` method on `Blueprint` and boolean-capable column modifiers such as:

```php
$definition->autoIncrement(false);
$definition->unsigned(false);
```

If your IDE does not pick it up automatically, make sure Composer dev autoload files are loaded:

```sh
composer dump-autoload
```

## Limitations

This package is intentionally focused on Laravel migration ergonomics and depends on the database schema information available through Laravel.

Some column types, database drivers, generated columns, indexes, or platform-specific attributes may require additional handling.

The package is unofficial and not maintained by the Laravel framework team.

## Testing

```sh
composer install
composer test
```

or directly:

```sh
./vendor/bin/pest
```

## Contributing

Contributions are welcome.

To work on the package locally:

```sh
git clone https://github.com/datarose-net/illuminate-blueprint-recall.git
cd illuminate-blueprint-recall
composer install
composer test
```

## License & Acknowledgments

This project is open source and released under the [GNU Affero General Public License v3.0 (AGPL-3.0)](https://www.gnu.org/licenses/agpl-3.0.html).

This package was created to make Laravel 11+ column migrations easier to maintain when changing existing columns.

Laravel is a trademark of Taylor Otwell. This package is unofficial and is not affiliated with or endorsed by Laravel or Taylor Otwell.

Copyright (C) 2020—present [Zoltán Rózsa](https://github.com/rozsazoltan) & [Datarose](https://github.com/datarose-net)
