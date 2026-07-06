<?php

declare(strict_types=1);

namespace Lalalili\CommerceKit\Cart\Data;

final readonly class CartLineSnapshot
{
    /**
     * @param array<string, mixed> $attributes
     * @param list<string> $conditionNames
     */
    public function __construct(
        public int|string $id,
        public string $name,
        public int $price,
        public int $quantity,
        public array $attributes,
        public int $salesPrice,
        public int $discount,
        public array $conditionNames,
    ) {
    }

    /**
     * @return array{
     *     id: int|string,
     *     name: string,
     *     price: int,
     *     quantity: int,
     *     attributes: array<string, mixed>,
     *     sales_price: int,
     *     discount: int,
     *     condition_name: list<string>
     * }
     */
    public function toLegacyArray(): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'price'          => $this->price,
            'quantity'       => $this->quantity,
            'attributes'     => $this->attributes,
            'sales_price'    => $this->salesPrice,
            'discount'       => $this->discount,
            'condition_name' => $this->conditionNames,
        ];
    }
}
