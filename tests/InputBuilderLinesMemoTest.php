<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Lalalili\CommerceCore\Services\CartPromotionLineResolver;
use Lalalili\CommerceKit\Promotion\AbstractCartPromotionRefreshInputBuilder;
use Lalalili\Discount\Contexts\PromotionSet;

final class CountingCartPromotionLineResolver extends CartPromotionLineResolver
{
    public int $payloadCalls = 0;

    public function payloads(
        iterable $content,
        callable $productExists,
        ?callable $attributesResolver = null,
        string $productAssociatedModel = 'Product',
    ): array {
        $this->payloadCalls++;

        return parent::payloads($content, $productExists, $attributesResolver, $productAssociatedModel);
    }
}

final class LinesMemoInputBuilder extends AbstractCartPromotionRefreshInputBuilder
{
    /**
     * @return array<int|string, PromotionSet>
     */
    protected function buildPromotionSetsByProductId(): array
    {
        return [];
    }
}

it('signature、version 與 build 共用同一份 lines 解析結果(resolver 只跑一次)', function (): void {
    $resolver = new CountingCartPromotionLineResolver(
        app(\Lalalili\CommerceCore\Services\CartItemAttributeNormalizer::class)
    );

    $builder = new LinesMemoInputBuilder(
        content: new Collection([
            (object) [
                'id'              => 10,
                'quantity'        => 2,
                'price'           => 150.0,
                'associatedModel' => 'Product',
                'attributes'      => [],
            ],
        ]),
        products: new Collection([10 => (object) ['id' => 10]]),
        lineResolver: $resolver,
    );

    $builder->promotionRefreshSignature();
    $builder->promotionVersion();
    $builder->build();
    $builder->lines();

    expect($resolver->payloadCalls)->toBe(1);
});
