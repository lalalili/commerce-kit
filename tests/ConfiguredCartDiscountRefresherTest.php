<?php

declare(strict_types=1);

use Lalalili\CommerceKit\Support\ConfiguredCartDiscountRefresher;
use Lalalili\Discount\DTOs\CartPromotionRefreshResult;
use Lalalili\ShoppingCart\Cart;

final class TestConfiguredCart extends Cart
{
}

final class TestConfiguredCartRefresher
{
    public static int $calls = 0;

    public static ?bool $force = null;

    public static function reset(): void
    {
        self::$calls = 0;
        self::$force = null;
    }

    public static function refreshDiscountConditions(Cart $cart, bool $force = false): CartPromotionRefreshResult
    {
        self::$calls++;
        self::$force = $force;

        return new CartPromotionRefreshResult([], [], []);
    }

    public static function invalidReturn(Cart $cart, bool $force = false): string
    {
        return 'invalid';
    }
}

beforeEach(function (): void {
    TestConfiguredCartRefresher::reset();
});

function testConfiguredCart(string $instanceName = 'checkout'): TestConfiguredCart
{
    return new TestConfiguredCart(null, null, $instanceName, 'configured_cart_'.$instanceName, []);
}

it('skips carts outside the configured cart class', function (): void {
    config()->set('commerce-kit.cart_class', TestConfiguredCart::class);
    config()->set('commerce-kit.discount_refresh.refresher', [TestConfiguredCartRefresher::class, 'refreshDiscountConditions']);

    $result = (new ConfiguredCartDiscountRefresher())->refreshDiscountConditions(
        new Cart(null, null, 'checkout', 'configured_cart_base', []),
        force: true,
    );

    expect($result)->toBeNull()
        ->and(TestConfiguredCartRefresher::$calls)->toBe(0);
});

it('calls the configured refresher for the configured cart class', function (): void {
    config()->set('commerce-kit.cart_class', TestConfiguredCart::class);
    config()->set('commerce-kit.discount_refresh.refresher', [TestConfiguredCartRefresher::class, 'refreshDiscountConditions']);

    $result = (new ConfiguredCartDiscountRefresher())->refreshDiscountConditions(
        testConfiguredCart(),
        force: true,
    );

    expect($result)->toBeInstanceOf(CartPromotionRefreshResult::class)
        ->and(TestConfiguredCartRefresher::$calls)->toBe(1)
        ->and(TestConfiguredCartRefresher::$force)->toBeTrue();
});

it('rejects invalid refresher return values', function (): void {
    config()->set('commerce-kit.cart_class', TestConfiguredCart::class);
    config()->set('commerce-kit.discount_refresh.refresher', [TestConfiguredCartRefresher::class, 'invalidReturn']);

    (new ConfiguredCartDiscountRefresher())->refreshDiscountConditions(testConfiguredCart());
})->throws(UnexpectedValueException::class);
