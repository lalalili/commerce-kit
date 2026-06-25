<?php

declare(strict_types=1);

use Lalalili\CommerceKit\Contracts\CartDiscountRefresher;
use Lalalili\ShoppingCart\Cart;
use Lalalili\ShoppingCart\CartCondition;

it('boots the package and loads its config', function (): void {
    expect(config('commerce-kit.cart_class'))->toBe(Cart::class);
    expect(config('commerce-kit.discount_refresh.instances'))->toBe(['shopping_cart', 'checkout']);
    expect(config('commerce-kit.discount_refresh.checkout_instance'))->toBe('checkout');
    expect(config('commerce-kit.coupon_condition.class'))->toBe(CartCondition::class);
});

it('exposes the cart discount refresher contract', function (): void {
    expect(interface_exists(CartDiscountRefresher::class))->toBeTrue();
});
