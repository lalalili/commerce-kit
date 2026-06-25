<?php

declare(strict_types=1);

namespace Lalalili\CommerceKit\Pipelines;

use Closure;
use Lalalili\CommerceCore\Services\PromotionRefreshPipelineMetadata;
use Lalalili\CommerceKit\Contracts\CartDiscountRefresher;
use Lalalili\Discount\DTOs\CartPromotionRefreshResult;
use Lalalili\Discount\Support\PricingTraceFormatter;
use Lalalili\ShoppingCart\Cart;
use Lalalili\ShoppingCart\CartContext;
use Lalalili\ShoppingCart\CartPipelineResult;
use Lalalili\ShoppingCart\Contracts\CartPipelineInterface;

/**
 * Recomputes promotion/coupon cart conditions after each cart mutation.
 *
 * Host-specific couplings are config-driven: the cart class it acts on, the
 * cart instance names it applies to, and which instance is treated as checkout
 * (forcing a refresh). The actual refresh is delegated to the host-bound
 * {@see CartDiscountRefresher}.
 */
final class CartDiscountRefreshPipeline implements CartPipelineInterface
{
    public function __construct(
        private readonly CartDiscountRefresher $refresher,
        private readonly PromotionRefreshPipelineMetadata $metadata,
    ) {
    }

    /**
     * @param  Closure(Cart, CartContext): CartPipelineResult  $next
     */
    public function handle(Cart $cart, CartContext $context, Closure $next): CartPipelineResult
    {
        $result = $next($cart, $context);

        /** @var class-string $cartClass */
        $cartClass = (string) config('commerce-kit.cart_class', Cart::class);
        /** @var array<int, string> $instances */
        $instances = (array) config('commerce-kit.discount_refresh.instances', []);
        $checkoutInstance = (string) config('commerce-kit.discount_refresh.checkout_instance', 'checkout');

        if (
            ! $cart instanceof $cartClass
            || $context->get('discount_pipeline_disabled', false) === true
            || ! in_array($cart->getInstanceName(), $instances, true)
        ) {
            return $result;
        }

        $startedAt = hrtime(true);
        $beforeHash = $cart->hash();
        $forceRefresh = $cart->getInstanceName() === $checkoutInstance
            || $context->get('force_discount_refresh', false) === true;

        $refreshResult = $this->refresher->refreshDiscountConditions($cart, force: $forceRefresh);
        $refreshMetadata = $refreshResult instanceof CartPromotionRefreshResult ? $refreshResult->metadata : [];
        $durationMs = (int) round((hrtime(true) - $startedAt) / 1_000_000);
        $pipelinePayload = $this->metadata->build(
            beforeHash: $beforeHash,
            afterHash: $cart->hash(),
            forceRefresh: $forceRefresh,
            refreshMetadata: $refreshMetadata,
            durationMs: $durationMs,
            itemCount: $cart->getContent()->count(),
            pricingTraceSummary: $refreshResult instanceof CartPromotionRefreshResult
                ? ['promotion' => PricingTraceFormatter::summarize($refreshResult->pricingTrace)]
                : ['promotion' => PricingTraceFormatter::summarize(null)],
        );

        return $result->merge(CartPipelineResult::make(
            changed: $pipelinePayload['changed'],
            metadata: $pipelinePayload['metadata'],
        ));
    }
}
