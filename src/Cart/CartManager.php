<?php

declare(strict_types=1);

namespace Lalalili\CommerceKit\Cart;

use Illuminate\Contracts\Container\Container;
use Lalalili\CommerceKit\Contracts\CartDiscountRefresher;
use Lalalili\Discount\DTOs\CartPromotionRefreshResult;
use Lalalili\ShoppingCart\Cart;
use UnexpectedValueException;

/**
 * 統一的 host 端購物車入口。
 *
 * Host 以 `commerce-kit.cart_manager.bindings` 宣告 container binding 名稱
 * (例:cptw `['cart' => 'shopping_cart', 'checkout' => 'checkout']`、
 * aitehub `['cart' => 'cart', 'checkout' => 'checkout']`),呼叫端統一以
 * `cart()` / `checkout()` / `instance()` 取用,不再依賴 host 專屬 binding 字串。
 */
class CartManager
{
    public function __construct(
        private readonly Container $container,
        private readonly CartDiscountRefresher $refresher,
        private readonly CartTotalsSnapshotFactory $totalsSnapshotFactory,
    ) {
    }

    public function cart(): Cart
    {
        return $this->instance('cart');
    }

    public function checkout(): Cart
    {
        return $this->instance('checkout');
    }

    /**
     * @param string $name cart_manager.bindings 的邏輯名稱(cart/checkout/…)
     */
    public function instance(string $name): Cart
    {
        $bindings = (array) config('commerce-kit.cart_manager.bindings', []);
        $binding = $bindings[$name] ?? $name;

        if (! is_string($binding) || $binding === '') {
            throw new UnexpectedValueException("commerce-kit.cart_manager.bindings.{$name} must be a container binding name.");
        }

        $cart = $this->container->make($binding);

        if (! $cart instanceof Cart) {
            throw new UnexpectedValueException("Container binding [{$binding}] must resolve to a shopping cart instance.");
        }

        return $cart;
    }

    public function refreshDiscounts(Cart $cart, bool $force = false): ?CartPromotionRefreshResult
    {
        return $this->refresher->refreshDiscountConditions($cart, $force);
    }

    /**
     * @param (callable(mixed): array<string, mixed>)|null $normalizeAttributes
     */
    public function totalsSnapshot(Cart $cart, ?callable $normalizeAttributes = null): Data\CartTotalsSnapshot
    {
        return $this->totalsSnapshotFactory->fromCart($cart, $normalizeAttributes);
    }
}
