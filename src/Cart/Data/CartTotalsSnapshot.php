<?php

declare(strict_types=1);

namespace Lalalili\CommerceKit\Cart\Data;

final readonly class CartTotalsSnapshot
{
    /**
     * @param array<int|string, CartLineSnapshot> $lines
     */
    public function __construct(
        public array $lines,
        public int $itemsOriginalSubtotal,
        public int $itemsSubtotal,
        public int $itemsDiscount,
        public int $subtotalWithoutConditions,
        public int $subtotal,
        public int $total,
        public int $totalDiscount,
    ) {
    }

    /**
     * @return array<int|string, array{
     *     id: int|string,
     *     name: string,
     *     price: int,
     *     quantity: int,
     *     attributes: array<string, mixed>,
     *     sales_price: int,
     *     discount: int,
     *     condition_name: list<string>
     * }>
     */
    public function legacyLines(): array
    {
        return array_map(
            static fn (CartLineSnapshot $line): array => $line->toLegacyArray(),
            $this->lines,
        );
    }

    /**
     * @return array{
     *     itemsOriginalSubtotal: string,
     *     itemsSubtotal: string,
     *     itemsDiscount: string,
     *     subtotalWithoutConditions: string,
     *     subtotal: string,
     *     total: string,
     *     totalDiscount: string
     * }
     */
    public function formattedTotals(): array
    {
        return [
            'itemsOriginalSubtotal'     => number_format($this->itemsOriginalSubtotal),
            'itemsSubtotal'             => number_format($this->itemsSubtotal),
            'itemsDiscount'             => number_format($this->itemsDiscount),
            'subtotalWithoutConditions' => number_format($this->subtotalWithoutConditions),
            'subtotal'                  => number_format($this->subtotal),
            'total'                     => number_format($this->total),
            'totalDiscount'             => number_format($this->totalDiscount),
        ];
    }
}
