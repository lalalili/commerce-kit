<?php

declare(strict_types=1);

namespace Lalalili\CommerceKit\Promotion;

use Illuminate\Support\Collection;
use Lalalili\CommerceCore\Services\CartItemAttributeNormalizer;
use Lalalili\CommerceCore\Services\CartPromotionLineResolver;
use Lalalili\CommerceCore\Services\CartPromotionRefreshInputFactoryService;
use Lalalili\Discount\Contexts\CartContext;
use Lalalili\Discount\Contexts\CartLineContext;
use Lalalili\Discount\Contexts\PromotionSet;
use Lalalili\Discount\Contracts\CartPromotionInputBuilderInterface;
use Lalalili\Discount\DTOs\CartPromotionRefreshInput;
use Lalalili\Discount\Support\PromotionRefreshFingerprint;
use UnexpectedValueException;

abstract class AbstractCartPromotionRefreshInputBuilder implements CartPromotionInputBuilderInterface
{
    /**
     * @var array<int|string, PromotionSet>|null
     */
    private ?array $promotionSetsByProductId = null;

    /**
     * @param  Collection<int|string, mixed>  $content
     * @param  Collection<int|string, mixed>  $products
     */
    public function __construct(
        protected readonly Collection $content,
        protected readonly Collection $products,
        protected readonly string $giftFulfillment = 'condition_only',
        private readonly ?CartItemAttributeNormalizer $attributeNormalizer = null,
        private readonly ?CartPromotionLineResolver $lineResolver = null,
        private readonly ?CartPromotionRefreshInputFactoryService $inputFactory = null,
        private readonly ?PromotionRefreshFingerprint $fingerprint = null,
    ) {
    }

    /**
     * @return list<CartLineContext>
     */
    public function lines(): array
    {
        return array_map(
            fn (object $line): CartLineContext => $this->ensureCartLineContext($line),
            $this->inputFactory()->linesFromPayloads($this->lineResolver()->payloads(
                content: $this->content,
                productExists: fn (mixed $productId): bool => $this->productExists($productId),
                attributesResolver: fn (mixed $item): array => $this->attributesForItem($item),
            )),
        );
    }

    /**
     * @return array<int|string, PromotionSet>
     */
    public function promotionSetsByProductId(): array
    {
        return $this->promotionSetsByProductId ??= $this->buildPromotionSetsByProductId();
    }

    public function cartContext(): CartContext
    {
        $context = $this->inputFactory()->cartContext();

        if (! $context instanceof CartContext) {
            throw new UnexpectedValueException('Cart promotion input factory must build a discount cart context.');
        }

        return $context;
    }

    public function giftFulfillment(): string
    {
        return $this->giftFulfillment;
    }

    public function build(): CartPromotionRefreshInput
    {
        $input = $this->inputFactory()->build(
            lines: $this->lines(),
            promotionSetsByProductId: $this->promotionSetsByProductId(),
            giftFulfillment: $this->giftFulfillment,
        );

        if (! $input instanceof CartPromotionRefreshInput) {
            throw new UnexpectedValueException('Cart promotion input factory must build a discount refresh input.');
        }

        return $input;
    }

    public function promotionVersion(): string
    {
        return $this->resolveFingerprint()->promotionVersion($this->promotionSetsByProductId());
    }

    public function promotionRefreshSignature(): string
    {
        return $this->resolveFingerprint()->promotionRefreshSignature(
            lines: $this->lines(),
            giftFulfillment: $this->giftFulfillment,
            promotionVersion: $this->promotionVersion(),
            lineAttributeKeys: $this->promotionRefreshLineAttributeKeys(),
        );
    }

    protected function productExists(mixed $productId): bool
    {
        return $this->productForId($productId) !== null;
    }

    protected function productForId(mixed $productId): mixed
    {
        if (! is_int($productId) && ! is_string($productId)) {
            return null;
        }

        if ($this->products->has($productId)) {
            return $this->products->get($productId);
        }

        return $this->products->firstWhere('id', $productId);
    }

    /**
     * @return array<string, mixed>
     */
    protected function attributesForItem(mixed $item): array
    {
        return $this->attributeNormalizer()->fromItem($item);
    }

    /**
     * @return list<string>
     */
    protected function promotionRefreshLineAttributeKeys(): array
    {
        return [];
    }

    /**
     * @return array<int|string, PromotionSet>
     */
    abstract protected function buildPromotionSetsByProductId(): array;

    protected function attributeNormalizer(): CartItemAttributeNormalizer
    {
        return $this->attributeNormalizer ?? app(CartItemAttributeNormalizer::class);
    }

    protected function lineResolver(): CartPromotionLineResolver
    {
        return $this->lineResolver ?? app(CartPromotionLineResolver::class);
    }

    protected function inputFactory(): CartPromotionRefreshInputFactoryService
    {
        return $this->inputFactory ?? app(CartPromotionRefreshInputFactoryService::class);
    }

    private function ensureCartLineContext(object $line): CartLineContext
    {
        if (! $line instanceof CartLineContext) {
            throw new UnexpectedValueException('Cart promotion input factory must build discount cart line contexts.');
        }

        return $line;
    }

    private function resolveFingerprint(): PromotionRefreshFingerprint
    {
        return $this->fingerprint ?? app(PromotionRefreshFingerprint::class);
    }
}
