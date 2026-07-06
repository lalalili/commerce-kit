<?php

declare(strict_types=1);

use Lalalili\CommerceKit\Cart\CartManager;
use Lalalili\CommerceKit\Contracts\CartDiscountRefresher;
use Lalalili\Discount\DTOs\CartPromotionRefreshResult;
use Lalalili\ShoppingCart\Cart;

final class CartManagerFakeRefresher implements CartDiscountRefresher
{
    public int $calls = 0;

    public ?bool $force = null;

    public function refreshDiscountConditions(Cart $cart, bool $force = false): ?CartPromotionRefreshResult
    {
        $this->calls++;
        $this->force = $force;

        return null;
    }
}

function cartManagerCart(string $instanceName): Cart
{
    return new Cart(null, null, $instanceName, 'cart_manager_'.$instanceName, []);
}

it('resolves cart and checkout through configured bindings', function (): void {
    config()->set('commerce-kit.cart_manager.bindings', [
        'cart'     => 'shopping_cart',
        'checkout' => 'checkout',
    ]);

    app()->instance('shopping_cart', cartManagerCart('shopping_cart'));
    app()->instance('checkout', cartManagerCart('checkout'));

    $manager = app(CartManager::class);

    expect($manager->cart()->getInstanceName())->toBe('shopping_cart')
        ->and($manager->checkout()->getInstanceName())->toBe('checkout');
});

it('falls back to the logical name when no binding is configured', function (): void {
    config()->set('commerce-kit.cart_manager.bindings', []);
    app()->instance('wishlist', cartManagerCart('wishlist'));

    expect(app(CartManager::class)->instance('wishlist')->getInstanceName())->toBe('wishlist');
});

it('rejects bindings that do not resolve to a cart', function (): void {
    config()->set('commerce-kit.cart_manager.bindings', ['cart' => 'not_a_cart']);
    app()->instance('not_a_cart', new stdClass());

    app(CartManager::class)->cart();
})->throws(UnexpectedValueException::class);

it('delegates discount refresh to the CartDiscountRefresher contract', function (): void {
    $fake = new CartManagerFakeRefresher();
    app()->instance(CartDiscountRefresher::class, $fake);

    $manager = app(CartManager::class);
    $manager->refreshDiscounts(cartManagerCart('checkout'), force: true);

    expect($fake->calls)->toBe(1)
        ->and($fake->force)->toBeTrue();
});
