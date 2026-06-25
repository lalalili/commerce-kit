<?php

declare(strict_types=1);

use Lalalili\CommerceCore\Services\PromotionRefreshPipelineMetadata;
use Lalalili\CommerceKit\Contracts\CartDiscountRefresher;
use Lalalili\CommerceKit\Pipelines\CartDiscountRefreshPipeline;
use Lalalili\Discount\DTOs\CartPromotionRefreshResult;
use Lalalili\ShoppingCart\Cart;
use Lalalili\ShoppingCart\CartContext;
use Lalalili\ShoppingCart\CartPipelineResult;

/**
 * Records whether/how the host refresher was invoked.
 */
function spyRefresher(): CartDiscountRefresher
{
    return new class () implements CartDiscountRefresher {
        public int $calls = 0;

        public ?bool $force = null;

        public function refreshDiscountConditions(Cart $cart, bool $force = false): ?CartPromotionRefreshResult
        {
            $this->calls++;
            $this->force = $force;

            return null;
        }
    };
}

function makeCart(string $instanceName): Cart
{
    return new Cart(null, null, $instanceName, 'kit_test_'.$instanceName, []);
}

it('skips the refresher for cart instances outside the configured list', function (): void {
    $refresher = spyRefresher();
    $pipeline = new CartDiscountRefreshPipeline($refresher, new PromotionRefreshPipelineMetadata());

    $passthrough = CartPipelineResult::make(changed: false);
    $result = $pipeline->handle(
        makeCart('wishlist'),
        new CartContext(),
        fn (): CartPipelineResult => $passthrough,
    );

    expect($refresher->calls)->toBe(0);
    expect($result)->toBe($passthrough);
});

it('runs the refresher with force for the checkout instance and merges metadata', function (): void {
    $refresher = spyRefresher();
    $pipeline = new CartDiscountRefreshPipeline($refresher, new PromotionRefreshPipelineMetadata());

    $result = $pipeline->handle(
        makeCart('checkout'),
        new CartContext(),
        fn (): CartPipelineResult => CartPipelineResult::make(changed: false),
    );

    expect($refresher->calls)->toBe(1);
    expect($refresher->force)->toBeTrue();
    expect($result)->toBeInstanceOf(CartPipelineResult::class);
    expect($result->metadata())->toHaveKey('discount_pipeline');
});

it('respects the discount_pipeline_disabled context flag', function (): void {
    $refresher = spyRefresher();
    $pipeline = new CartDiscountRefreshPipeline($refresher, new PromotionRefreshPipelineMetadata());

    $pipeline->handle(
        makeCart('checkout'),
        new CartContext(['discount_pipeline_disabled' => true]),
        fn (): CartPipelineResult => CartPipelineResult::make(changed: false),
    );

    expect($refresher->calls)->toBe(0);
});
