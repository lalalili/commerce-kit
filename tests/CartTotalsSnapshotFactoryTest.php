<?php

declare(strict_types=1);

use Lalalili\CommerceKit\Cart\CartManager;
use Lalalili\CommerceKit\Cart\CartTotalsSnapshotFactory;
use Lalalili\ShoppingCart\Cart;
use Lalalili\ShoppingCart\CartCondition;
use Lalalili\ShoppingCart\ItemAttributeCollection;

final class InMemoryCartSession
{
    /**
     * @var array<string, mixed>
     */
    private array $values = [];

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->values[$key] ?? $default;
    }

    public function put(string $key, mixed $value): void
    {
        $this->values[$key] = $value;
    }
}

function snapshotCart(): Cart
{
    return new Cart(new InMemoryCartSession(), null, 'snapshot', 'cart_totals_snapshot', [
        'format_numbers' => true,
        'rounding'       => [
            'item_price'                  => ['precision' => 0, 'mode' => 'floor'],
            'item_price_before_quantity'  => null,
            'line_subtotal'               => null,
            'subtotal_without_conditions' => null,
            'subtotal'                    => ['precision' => 0, 'mode' => 'half_up'],
            'total'                       => ['precision' => 0, 'mode' => 'half_up'],
            'per_condition_step'          => true,
        ],
    ]);
}

it('builds integer cart totals and keyed legacy line payloads', function (): void {
    $cart = snapshotCart();
    $cart->add([
        'id'         => 10,
        'name'       => 'Discounted item',
        'price'      => 333,
        'quantity'   => 3,
        'attributes' => ['type' => 'book'],
    ]);
    $cart->addItemCondition(10, new CartCondition([
        'name'   => '九折活動',
        'type'   => 'discount',
        'value'  => '-10%',
        'target' => 'item',
    ]));
    $cart->condition(new CartCondition([
        'name'   => '會員折抵',
        'type'   => 'member_coupon',
        'value'  => '-50',
        'target' => 'total',
        'order'  => 1,
    ]));

    $snapshot = app(CartTotalsSnapshotFactory::class)->fromCart($cart);

    expect($snapshot->itemsOriginalSubtotal)->toBe(999)
        ->and($snapshot->itemsSubtotal)->toBe(897)
        ->and($snapshot->itemsDiscount)->toBe(102)
        ->and($snapshot->subtotalWithoutConditions)->toBe(999)
        ->and($snapshot->subtotal)->toBe(897)
        ->and($snapshot->total)->toBe(847)
        ->and($snapshot->totalDiscount)->toBe(50)
        ->and($snapshot->legacyLines()[10])->toMatchArray([
            'id'             => 10,
            'name'           => 'Discounted item',
            'price'          => 333,
            'quantity'       => 3,
            'attributes'     => ['type' => 'book'],
            'sales_price'    => 897,
            'discount'       => 102,
            'condition_name' => ['九折活動'],
        ])
        ->and($snapshot->formattedTotals())->toMatchArray([
            'itemsOriginalSubtotal'     => '999',
            'itemsSubtotal'             => '897',
            'itemsDiscount'             => '102',
            'subtotalWithoutConditions' => '999',
            'subtotal'                  => '897',
            'total'                     => '847',
            'totalDiscount'             => '50',
        ]);
});

it('normalizes attributes through an optional host callback', function (): void {
    $cart = snapshotCart();
    $cart->add([
        'id'         => 'course-1',
        'name'       => 'Course',
        'price'      => 1200,
        'quantity'   => 1,
        'attributes' => ['type' => 'course'],
    ]);

    $snapshot = app(CartTotalsSnapshotFactory::class)->fromCart(
        $cart,
        static fn (mixed $attributes): array => [
            'normalized' => $attributes instanceof ItemAttributeCollection ? $attributes->get('type') : null,
        ],
    );

    expect($snapshot->legacyLines()['course-1']['attributes'])->toBe(['normalized' => 'course']);
});

it('exposes totals snapshots through the cart manager', function (): void {
    $cart = snapshotCart();
    $cart->add([
        'id'       => 5,
        'name'     => 'Managed cart item',
        'price'    => 1500,
        'quantity' => 2,
    ]);

    $snapshot = app(CartManager::class)->totalsSnapshot($cart);

    expect($snapshot->itemsOriginalSubtotal)->toBe(3000)
        ->and($snapshot->total)->toBe(3000);
});
