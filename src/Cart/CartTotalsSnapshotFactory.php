<?php

declare(strict_types=1);

namespace Lalalili\CommerceKit\Cart;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Lalalili\CommerceKit\Cart\Data\CartLineSnapshot;
use Lalalili\CommerceKit\Cart\Data\CartTotalsSnapshot;
use Lalalili\ShoppingCart\Cart;
use Lalalili\ShoppingCart\CartCondition;
use Lalalili\ShoppingCart\ItemAttributeCollection;
use Lalalili\ShoppingCart\ItemCollection;

final class CartTotalsSnapshotFactory
{
    /**
     * @param (callable(mixed): array<string, mixed>)|null $normalizeAttributes
     */
    public function fromCart(Cart $cart, ?callable $normalizeAttributes = null): CartTotalsSnapshot
    {
        $lines = [];
        $itemsOriginalSubtotal = 0;
        $itemsSubtotal = 0;

        foreach ($cart->getContent() as $item) {
            $line = $this->line($item, $normalizeAttributes);
            $lines[$line->id] = $line;
            $itemsOriginalSubtotal += $line->price * $line->quantity;
            $itemsSubtotal += $line->salesPrice;
        }

        $subtotalWithoutConditions = (int) round((float) $cart->getSubTotalWithoutConditions(false));
        $subtotal = (int) round((float) $cart->getSubTotal(false));
        $total = (int) round((float) $cart->getTotal(false));

        return new CartTotalsSnapshot(
            lines: $lines,
            itemsOriginalSubtotal: $itemsOriginalSubtotal,
            itemsSubtotal: $itemsSubtotal,
            itemsDiscount: max(0, $itemsOriginalSubtotal - $itemsSubtotal),
            subtotalWithoutConditions: $subtotalWithoutConditions,
            subtotal: $subtotal,
            total: $total,
            totalDiscount: max(0, $subtotal - $total),
        );
    }

    /**
     * @param (callable(mixed): array<string, mixed>)|null $normalizeAttributes
     */
    private function line(ItemCollection $item, ?callable $normalizeAttributes): CartLineSnapshot
    {
        $id = $item->get('id');
        $name = (string) $item->get('name', '');
        $price = (int) round((float) $item->get('price', 0));
        $quantity = (int) round((float) $item->get('quantity', 0));
        $salesPrice = (int) round((float) $item->getPriceSumWithConditions(false));

        return new CartLineSnapshot(
            id: is_int($id) || is_string($id) ? $id : (string) $id,
            name: $name,
            price: $price,
            quantity: $quantity,
            attributes: $this->normalizeAttributes($item->get('attributes', []), $normalizeAttributes),
            salesPrice: $salesPrice,
            discount: max(0, ($price * $quantity) - $salesPrice),
            conditionNames: $this->conditionNames($item),
        );
    }

    /**
     * @param (callable(mixed): array<string, mixed>)|null $normalizeAttributes
     * @return array<string, mixed>
     */
    private function normalizeAttributes(mixed $attributes, ?callable $normalizeAttributes): array
    {
        if ($normalizeAttributes !== null) {
            return $normalizeAttributes($attributes);
        }

        if ($attributes instanceof ItemAttributeCollection || $attributes instanceof Collection) {
            return $attributes->toArray();
        }

        if ($attributes instanceof Arrayable) {
            $attributes = $attributes->toArray();
        }

        return is_array($attributes) ? $attributes : [];
    }

    /**
     * @return list<string>
     */
    private function conditionNames(ItemCollection $item): array
    {
        $conditions = $item->getConditions();

        if ($conditions instanceof CartCondition) {
            return [$conditions->getName()];
        }

        $names = [];

        foreach ($conditions as $condition) {
            $names[] = $condition->getName();
        }

        return $names;
    }
}
