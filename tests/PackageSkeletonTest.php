<?php

declare(strict_types=1);

use Lalalili\CommerceKit\Contracts\CartDiscountRefresher;
use Lalalili\CommerceKit\Coupons\CouponCartConditionFactory;
use Lalalili\CommerceKit\Pipelines\CartDiscountRefreshPipeline;
use Lalalili\CommerceCore\Contracts\CheckoutOrderBuilder;
use Lalalili\ShoppingCart\Cart;
use Lalalili\ShoppingCart\CartCondition;

it('boots the package and loads its config', function (): void {
    expect(config('commerce-kit.cart_class'))->toBe(Cart::class);
    expect(config('commerce-kit.discount_refresh.instances'))->toBe(['shopping_cart', 'checkout']);
    expect(config('commerce-kit.discount_refresh.checkout_instance'))->toBe('checkout');
    expect(config('commerce-kit.checkout_order.items.method'))->toBe('getContent');
    expect(config('commerce-kit.coupon_condition.class'))->toBe(CartCondition::class);
});

it('exposes the cart discount refresher contract', function (): void {
    expect(interface_exists(CartDiscountRefresher::class))->toBeTrue();
});

it('exposes the checkout order builder contract', function (): void {
    expect(interface_exists(CheckoutOrderBuilder::class))->toBeTrue();
});

it('registers reusable glue services as singletons', function (): void {
    expect(app(CouponCartConditionFactory::class))
        ->toBe(app(CouponCartConditionFactory::class));

    app()->instance(CartDiscountRefresher::class, new class () implements CartDiscountRefresher {
        public function refreshDiscountConditions(
            \Lalalili\ShoppingCart\Cart $cart,
            bool $force = false,
        ): ?\Lalalili\Discount\DTOs\CartPromotionRefreshResult {
            return null;
        }
    });

    expect(app(CartDiscountRefreshPipeline::class))
        ->toBe(app(CartDiscountRefreshPipeline::class));
});
