<?php

declare(strict_types=1);

use Datarose\BlueprintRecall\Helpers\ColumnIndexInspector;

it('detects a single-column unique index', function (): void {
    $indexes = collect([
        [
            'name' => 'users_email_unique',
            'columns' => ['email'],
            'unique' => true,
        ],
    ]);

    expect(ColumnIndexInspector::isSingleColumnUnique($indexes, 'email'))->toBeTrue();
});

it('does not treat composite unique indexes as single-column unique indexes', function (): void {
    $indexes = collect([
        [
            'name' => 'users_email_tenant_id_unique',
            'columns' => ['email', 'tenant_id'],
            'unique' => true,
        ],
    ]);

    expect(ColumnIndexInspector::isSingleColumnUnique($indexes, 'email'))->toBeFalse();
});

it('does not treat normal indexes as unique indexes', function (): void {
    $indexes = collect([
        [
            'name' => 'users_email_index',
            'columns' => ['email'],
            'unique' => false,
        ],
    ]);

    expect(ColumnIndexInspector::isSingleColumnUnique($indexes, 'email'))->toBeFalse();
});
