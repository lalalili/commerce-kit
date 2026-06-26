<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Lalalili\CommerceCore\Contracts\CheckoutOrderBuilder;
use Lalalili\CommerceCore\DTOs\CheckoutOrderData;
use Lalalili\CommerceKit\Support\ConfiguredCheckoutOrderBuilder;

final class TestCheckoutOrderBuilderCart
{
    /**
     * @param  Collection<int|string, mixed>  $content
     */
    public function __construct(private Collection $content)
    {
    }

    /**
     * @return Collection<int|string, mixed>
     */
    public function getContent(): Collection
    {
        return $this->content;
    }
}

final class TestCheckoutOrderBuilderServiceCart
{
    /**
     * @param  Collection<int|string, mixed>  $cartContent
     */
    public function __construct(public Collection $cartContent)
    {
    }
}

function testCheckoutOrderLine(int $id = 10): object
{
    return (object) [
        'id'              => $id,
        'name'            => 'Product '.$id,
        'price'           => 120,
        'quantity'        => 2,
        'associatedModel' => 'Product',
        'attributes'      => ['type' => 'ebook'],
    ];
}

it('is registered as the default commerce-core checkout order builder', function (): void {
    expect(app(CheckoutOrderBuilder::class))->toBeInstanceOf(ConfiguredCheckoutOrderBuilder::class);
});

it('builds checkout order data from cart getContent lines', function (): void {
    config()->set('commerce-kit.cart_class', TestCheckoutOrderBuilderCart::class);
    config()->set('commerce-kit.checkout_order.items.method', 'getContent');
    config()->set('commerce-kit.checkout_order.items.property', null);

    $cart = new TestCheckoutOrderBuilderCart(new Collection([
        testCheckoutOrderLine(),
    ]));

    $order = app(CheckoutOrderBuilder::class)->build($cart, [
        'payment_type' => 'credit',
        'user_id'      => 1,
    ]);

    expect($order)->toBeInstanceOf(CheckoutOrderData::class)
        ->and($order->lines)->toHaveCount(1)
        ->and($order->attributes['payment_type'])->toBe('credit');
});

it('builds checkout order data from a configured cart content property', function (): void {
    config()->set('commerce-kit.checkout_order.expected_cart_class', TestCheckoutOrderBuilderServiceCart::class);
    config()->set('commerce-kit.checkout_order.items.method', null);
    config()->set('commerce-kit.checkout_order.items.property', 'cartContent');
    config()->set('commerce-kit.checkout_order.normalize_item_attributes', true);

    $cart = new TestCheckoutOrderBuilderServiceCart(new Collection([
        testCheckoutOrderLine(),
    ]));

    $order = app(CheckoutOrderBuilder::class)->build($cart, ['user_id' => 1]);

    expect($order->lines)->toHaveCount(1)
        ->and($order->lines[0]->attributes)->toBe(['type' => 'ebook']);
});

it('rejects item sources that do not resolve to an iterable value', function (): void {
    config()->set('commerce-kit.checkout_order.expected_cart_class', TestCheckoutOrderBuilderServiceCart::class);
    config()->set('commerce-kit.checkout_order.items.method', null);
    config()->set('commerce-kit.checkout_order.items.property', 'missing');

    app(CheckoutOrderBuilder::class)->build(new TestCheckoutOrderBuilderServiceCart(new Collection()));
})->throws(InvalidArgumentException::class, 'commerce-kit.checkout_order.items must resolve to an iterable value.');
