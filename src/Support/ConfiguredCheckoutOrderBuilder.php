<?php

declare(strict_types=1);

namespace Lalalili\CommerceKit\Support;

use InvalidArgumentException;
use Lalalili\CommerceCore\Contracts\CheckoutOrderBuilder;
use Lalalili\CommerceCore\DTOs\CheckoutOrderData;
use Lalalili\CommerceCore\Services\CartItemAttributeNormalizer;
use Lalalili\CommerceCore\Services\CheckoutOrderBuilderService;
use Lalalili\ShoppingCart\Cart;

final class ConfiguredCheckoutOrderBuilder implements CheckoutOrderBuilder
{
    public function __construct(
        private readonly CheckoutOrderBuilderService $orders,
        private readonly CartItemAttributeNormalizer $itemAttributes,
    ) {
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function build(mixed $checkoutCart, array $attributes = []): CheckoutOrderData
    {
        return $this->orders->build(
            checkoutCart: $checkoutCart,
            expectedCartClass: $this->expectedCartClass(),
            itemsResolver: fn (mixed $cart): iterable => $this->items($cart),
            attributes: $attributes,
            fallbackUserId: auth()->id(),
            itemAttributesResolver: $this->itemAttributesResolver(),
            invalidCartMessage: $this->invalidCartMessage(),
        );
    }

    /**
     * @return class-string|null
     */
    private function expectedCartClass(): ?string
    {
        $configured = config('commerce-kit.checkout_order.expected_cart_class');
        if ($configured === null) {
            $configured = config('commerce-kit.cart_class', Cart::class);
        }

        return is_string($configured) && class_exists($configured) ? $configured : null;
    }

    private function invalidCartMessage(): ?string
    {
        $message = config('commerce-kit.checkout_order.invalid_cart_message');

        return is_string($message) && $message !== '' ? $message : null;
    }

    /**
     * @return iterable<mixed>
     */
    private function items(mixed $cart): iterable
    {
        $source = $this->itemSource($cart);
        $collectionMethod = config('commerce-kit.checkout_order.items.collection_method', 'all');

        if (is_object($source) && is_string($collectionMethod) && $collectionMethod !== '' && method_exists($source, $collectionMethod)) {
            $source = $source->{$collectionMethod}();
        }

        if (! is_iterable($source)) {
            throw new InvalidArgumentException('commerce-kit.checkout_order.items must resolve to an iterable value.');
        }

        return $source;
    }

    private function itemSource(mixed $cart): mixed
    {
        $method = config('commerce-kit.checkout_order.items.method');
        if (is_string($method) && $method !== '' && is_object($cart) && method_exists($cart, $method)) {
            return $cart->{$method}();
        }

        $property = config('commerce-kit.checkout_order.items.property');
        if (is_string($property) && $property !== '' && is_object($cart)) {
            return data_get($cart, $property);
        }

        return [];
    }

    /**
     * @return callable(mixed): array<string, mixed>|null
     */
    private function itemAttributesResolver(): ?callable
    {
        if (! (bool) config('commerce-kit.checkout_order.normalize_item_attributes', false)) {
            return null;
        }

        return fn (mixed $item): array => $this->itemAttributes->normalize($item->attributes ?? []);
    }
}
