<?php

declare(strict_types=1);

use App\Domain\Inventory\Actions\InsufficientStockException;
use App\Domain\Inventory\Actions\RecordStockMovementAction;
use App\Domain\Inventory\Models\InventoryItem;
use App\Domain\Inventory\Models\StockMovement;
use App\Support\Enums\StockMovementType;
use App\Support\Enums\UserRole;
use App\Support\Tenancy\CurrentSchool;

function stockItem(int $schoolId, int $stock = 0, array $attrs = []): InventoryItem
{
    return CurrentSchool::runAs($schoolId, function () use ($schoolId, $stock, $attrs): InventoryItem {
        $item = InventoryItem::factory()->create(['school_id' => $schoolId] + $attrs);

        if ($stock !== 0) {
            StockMovement::factory()->create([
                'school_id' => $schoolId,
                'inventory_item_id' => $item->id,
                'quantity' => $stock,
            ]);
        }

        return $item;
    });
}

function stockOf(InventoryItem $item): int
{
    return CurrentSchool::runAs((int) $item->school_id, fn (): int => $item->currentStock());
}

test('owner and accountant can open inventory, teacher and parent cannot', function (UserRole $role, int $status): void {
    $user = schoolUser($role);
    stockItem($user->school_id);

    $this->actingAs($user)->get(route('inventory.index'))->assertStatus($status);
})->with([
    [UserRole::Owner, 200],
    [UserRole::Accountant, 200],
    [UserRole::Teacher, 403],
    [UserRole::Parent, 403],
]);

test('guest is redirected to login', function (): void {
    $this->get(route('inventory.index'))->assertRedirect(route('login'));
});

test('list shows bilingual labels and a low stock badge', function (): void {
    $owner = schoolUser(UserRole::Owner);
    stockItem($owner->school_id, 3, ['name' => 'Chalk', 'low_stock_threshold' => 5]);
    stockItem($owner->school_id, 50, ['name' => 'Paper', 'low_stock_threshold' => 5]);

    $response = $this->actingAs($owner)->get(route('inventory.index'));

    $response->assertOk()->assertSee('Chalk')->assertSee('Paper')->assertSee('ইনভেন্টরি');
    expect(substr_count($response->getContent(), 'কম স্টক'))->toBe(1);
});

test('owner creates an item with Bangla digits normalised', function (): void {
    $owner = schoolUser(UserRole::Owner);

    $this->actingAs($owner)->post(route('inventory.store'), [
        'name' => 'Marker',
        'unit' => 'pcs',
        'low_stock_threshold' => '১০',
    ])->assertRedirect(route('inventory.index'));

    $item = CurrentSchool::runAs($owner->school_id, fn () => InventoryItem::first());
    expect($item->name)->toBe('Marker')->and($item->low_stock_threshold)->toBe(10);
});

test('item creation is validated and names are unique per school', function (): void {
    $owner = schoolUser(UserRole::Owner);
    stockItem($owner->school_id, 0, ['name' => 'Marker']);

    $this->actingAs($owner)->post(route('inventory.store'), ['name' => '', 'unit' => '', 'low_stock_threshold' => '-1'])
        ->assertSessionHasErrors(['name', 'unit', 'low_stock_threshold']);

    $this->actingAs($owner)->post(route('inventory.store'), ['name' => 'Marker', 'unit' => 'pcs', 'low_stock_threshold' => 1])
        ->assertSessionHasErrors('name');
});

test('teacher cannot create items or record movements', function (): void {
    $teacher = schoolUser(UserRole::Teacher);
    $item = stockItem($teacher->school_id, 5);

    $this->actingAs($teacher)->post(route('inventory.store'), ['name' => 'X', 'unit' => 'pcs', 'low_stock_threshold' => 1])->assertForbidden();
    $this->actingAs($teacher)->post(route('inventory.movements.store', $item), ['type' => 'in', 'quantity' => 1, 'reason' => 'x'])->assertForbidden();
    expect(stockOf($item))->toBe(5);
});

test('accountant records stock in and out and stock is the ledger sum', function (): void {
    $accountant = schoolUser(UserRole::Accountant);
    $item = stockItem($accountant->school_id);

    $this->actingAs($accountant)->post(route('inventory.movements.store', $item), [
        'type' => 'in', 'quantity' => '২০', 'reason' => 'Purchase', 'note' => 'Invoice 7',
    ])->assertRedirect(route('inventory.show', $item));

    $this->actingAs($accountant)->post(route('inventory.movements.store', $item), [
        'type' => 'out', 'quantity' => 8, 'reason' => 'Classroom use',
    ])->assertRedirect(route('inventory.show', $item));

    expect(stockOf($item))->toBe(12);

    $movements = CurrentSchool::runAs($accountant->school_id, fn () => StockMovement::orderBy('id')->get());
    expect($movements->pluck('quantity')->all())->toBe([20, -8])
        ->and($movements->first()->user_id)->toBe($accountant->id)
        ->and($movements->first()->note)->toBe('Invoice 7');
});

test('out movement that would go below zero is rejected and nothing is written', function (): void {
    $owner = schoolUser(UserRole::Owner);
    $item = stockItem($owner->school_id, 5);

    $this->actingAs($owner)->post(route('inventory.movements.store', $item), [
        'type' => 'out', 'quantity' => 6, 'reason' => 'Use',
    ])->assertSessionHasErrors('quantity');

    expect(stockOf($item))->toBe(5)
        ->and(CurrentSchool::runAs($owner->school_id, fn () => StockMovement::count()))->toBe(1);
});

test('out movement taking stock exactly to zero is allowed', function (): void {
    $owner = schoolUser(UserRole::Owner);
    $item = stockItem($owner->school_id, 5);

    $this->actingAs($owner)->post(route('inventory.movements.store', $item), [
        'type' => 'out', 'quantity' => 5, 'reason' => 'Use',
    ])->assertSessionDoesntHaveErrors();

    expect(stockOf($item))->toBe(0);
});

test('adjustment accepts a signed quantity but never goes below zero', function (): void {
    $owner = schoolUser(UserRole::Owner);
    $item = stockItem($owner->school_id, 5);

    $this->actingAs($owner)->post(route('inventory.movements.store', $item), [
        'type' => 'adjustment', 'quantity' => -2, 'reason' => 'Stock count',
    ])->assertSessionDoesntHaveErrors();
    expect(stockOf($item))->toBe(3);

    $this->actingAs($owner)->post(route('inventory.movements.store', $item), [
        'type' => 'adjustment', 'quantity' => -4, 'reason' => 'Stock count',
    ])->assertSessionHasErrors('quantity');
    expect(stockOf($item))->toBe(3);
});

test('movement validation rejects bad input', function (array $payload, string $field): void {
    $owner = schoolUser(UserRole::Owner);
    $item = stockItem($owner->school_id, 5);

    $this->actingAs($owner)->post(route('inventory.movements.store', $item), $payload)->assertSessionHasErrors($field);
})->with([
    'unknown type' => [['type' => 'bogus', 'quantity' => 1, 'reason' => 'x'], 'type'],
    'zero quantity' => [['type' => 'in', 'quantity' => 0, 'reason' => 'x'], 'quantity'],
    'negative in' => [['type' => 'in', 'quantity' => -3, 'reason' => 'x'], 'quantity'],
    'zero adjustment' => [['type' => 'adjustment', 'quantity' => 0, 'reason' => 'x'], 'quantity'],
    'fraction' => [['type' => 'in', 'quantity' => '1.5', 'reason' => 'x'], 'quantity'],
    'missing reason' => [['type' => 'in', 'quantity' => 1, 'reason' => ''], 'reason'],
    'absurd quantity' => [['type' => 'in', 'quantity' => 99999999999, 'reason' => 'x'], 'quantity'],
]);

test('ledger rows cannot be edited or deleted', function (): void {
    $owner = schoolUser(UserRole::Owner);
    $item = stockItem($owner->school_id, 5);

    CurrentSchool::runAs($owner->school_id, function (): void {
        $movement = StockMovement::first();

        expect(fn () => $movement->update(['quantity' => 99]))->toThrow(LogicException::class)
            ->and(fn () => $movement->delete())->toThrow(LogicException::class);
    });

    expect(stockOf($item))->toBe(5);
});

test('action rejects an overdraw with a typed exception', function (): void {
    $owner = schoolUser(UserRole::Owner);
    $item = stockItem($owner->school_id, 2);

    expect(fn () => (new RecordStockMovementAction)->execute($item, StockMovementType::Out, 3, 'Use'))
        ->toThrow(InsufficientStockException::class);
});

test('action rejects a zero quantity', function (): void {
    $owner = schoolUser(UserRole::Owner);
    $item = stockItem($owner->school_id, 2);

    expect(fn () => (new RecordStockMovementAction)->execute($item, StockMovementType::In, 0, 'x'))
        ->toThrow(InvalidArgumentException::class);
});

test('detail page shows the ledger and stock', function (): void {
    $owner = schoolUser(UserRole::Owner);
    $item = stockItem($owner->school_id, 7, ['name' => 'Crayons']);

    $this->actingAs($owner)->get(route('inventory.show', $item))
        ->assertOk()->assertSee('Crayons')->assertSee('Purchase')->assertSee('স্টক খাতা');
});

test('another school gets 404 on item pages and movements', function (): void {
    $owner = schoolUser(UserRole::Owner);
    $other = schoolUser(UserRole::Owner);
    $item = stockItem($other->school_id, 5, ['name' => 'Secret']);

    $this->actingAs($owner)->get(route('inventory.show', $item))->assertNotFound();
    $this->actingAs($owner)->post(route('inventory.movements.store', $item), ['type' => 'in', 'quantity' => 1, 'reason' => 'x'])->assertNotFound();
    $this->actingAs($owner)->get(route('inventory.index'))->assertDontSee('Secret');
    expect(stockOf($item))->toBe(5);
});

test('disabled module returns 404 and hides the nav link', function (): void {
    $owner = schoolUser(UserRole::Owner);
    config(['kcam.modules.inventory' => false]);

    $this->actingAs($owner)->get(route('inventory.index'))->assertNotFound();
    $this->actingAs($owner)->get(route('dashboard'))->assertOk()->assertDontSee(route('inventory.index'));
});

test('nav link shows when the module is on', function (): void {
    $owner = schoolUser(UserRole::Owner);

    $this->actingAs($owner)->get(route('dashboard'))->assertSee(route('inventory.index'));
});

test('output is escaped', function (): void {
    $owner = schoolUser(UserRole::Owner);
    stockItem($owner->school_id, 1, ['name' => '<script>alert(1)</script>']);

    $this->actingAs($owner)->get(route('inventory.index'))->assertDontSee('<script>alert(1)</script>', false);
});
