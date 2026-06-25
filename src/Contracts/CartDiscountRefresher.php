<?php

declare(strict_types=1);

namespace Lalalili\CommerceKit\Contracts;

use Lalalili\Discount\DTOs\CartPromotionRefreshResult;
use Lalalili\ShoppingCart\Cart;

/**
 * Seam between the kit's discount-refresh pipeline and the host cart service.
 *
 * The host binds an implementation (typically its CartService) that recomputes
 * promotion/coupon conditions on the given cart and returns the refresh result.
 */
interface CartDiscountRefresher
{
    public function refreshDiscountConditions(Cart $cart, bool $force = false): ?CartPromotionRefreshResult;
}
