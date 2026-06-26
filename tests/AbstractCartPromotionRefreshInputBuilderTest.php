<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Lalalili\CommerceKit\Promotion\AbstractCartPromotionRefreshInputBuilder;
use Lalalili\Discount\Contexts\PromotionContext;
use Lalalili\Discount\Contexts\PromotionSet;
use Lalalili\Discount\DTOs\CartPromotionRefreshInput;

final class TestCartPromotionRefreshInputBuilder extends AbstractCartPromotionRefreshInputBuilder
{
    public int $promotionBuilds = 0;

    /**
     * @return array<int|string, PromotionSet>
     */
    protected function buildPromotionSetsByProductId(): array
    {
        $this->promotionBuilds++;

        return [
            10 => new PromotionSet([
                new PromotionContext(
                    type: 1,
                    sort: 5,
                    eventId: 99,
                    attributes: ['updated_at_timestamp' => 123],
                ),
            ]),
        ];
    }

    /**
     * @return list<string>
     */
    protected function promotionRefreshLineAttributeKeys(): array
    {
        return ['bundle'];
    }
}

/**
 * @param  array<int|string, mixed>  $items
 * @return Collection<int|string, mixed>
 */
function testBuilderCollection(array $items = []): Collection
{
    return new Collection($items);
}

it('builds discount refresh input from cart content and host promotion sets', function (): void {
    $builder = new TestCartPromotionRefreshInputBuilder(
        content: testBuilderCollection([
            (object) [
                'id' => 10,
                'quantity' => 2,
                'price' => 150.0,
                'associatedModel' => 'Product',
                'attributes' => ['bundle' => true],
            ],
            (object) [
                'id' => 11,
                'quantity' => 1,
                'price' => 80.0,
                'associatedModel' => 'Product',
                'attributes' => [],
            ],
        ]),
        products: testBuilderCollection([
            10 => (object) ['id' => 10],
        ]),
        giftFulfillment: 'add_item',
    );

    $input = $builder->build();

    expect($input)->toBeInstanceOf(CartPromotionRefreshInput::class)
        ->and($input->giftFulfillment)->toBe('add_item')
        ->and($input->lines)->toHaveCount(1)
        ->and($input->lines[0]->productId)->toBe(10)
        ->and($input->lines[0]->attributes)->toBe(['bundle' => true])
        ->and($input->promotionSetsByProductId)->toHaveKey(10)
        ->and($builder->promotionVersion())->toBeString()
        ->and($builder->promotionRefreshSignature())->toBeString();
});

it('caches host promotion set construction', function (): void {
    $builder = new TestCartPromotionRefreshInputBuilder(
        content: testBuilderCollection(),
        products: testBuilderCollection(),
    );

    expect($builder->promotionSetsByProductId())->toHaveKey(10)
        ->and($builder->promotionSetsByProductId())->toHaveKey(10)
        ->and($builder->promotionBuilds)->toBe(1);
});
