<?php

declare(strict_types=1);

namespace Lalalili\CommerceKit\Support;

use Lalalili\CommerceKit\Contracts\CartDiscountRefresher;
use Lalalili\Discount\DTOs\CartPromotionRefreshResult;
use Lalalili\ShoppingCart\Cart;
use RuntimeException;
use UnexpectedValueException;

final class ConfiguredCartDiscountRefresher implements CartDiscountRefresher
{
    public function refreshDiscountConditions(Cart $cart, bool $force = false): ?CartPromotionRefreshResult
    {
        /** @var class-string<Cart> $cartClass */
        $cartClass = (string) config('commerce-kit.cart_class', Cart::class);

        if (! $cart instanceof $cartClass) {
            return null;
        }

        $callback = config('commerce-kit.discount_refresh.refresher');

        if (! is_callable($callback)) {
            throw new RuntimeException('commerce-kit.discount_refresh.refresher must be a callable.');
        }

        $result = app()->call($callback, [
            'cart'  => $cart,
            'force' => $force,
        ]);

        if ($result !== null && ! $result instanceof CartPromotionRefreshResult) {
            throw new UnexpectedValueException('commerce-kit discount refresher must return null or CartPromotionRefreshResult.');
        }

        return $result;
    }
}
