<?php

declare(strict_types=1);

namespace Lalalili\CommerceKit\Coupons;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Lalalili\CommerceCore\Services\CouponDataFactory;
use Lalalili\CommerceCore\Services\CouponInventoryService;
use Lalalili\Discount\Contracts\CouponRepositoryInterface;
use Lalalili\Discount\DTOs\CouponData;
use Lalalili\Discount\Enums\CouponKind;

/**
 * Config-light base for the host's discount {@see CouponRepositoryInterface}.
 *
 * Consolidates the duplicated host plumbing: turning a host coupon model into a
 * discount {@see CouponData} (via commerce-core {@see CouponDataFactory}) and
 * reserving promotion inventory (via {@see CouponInventoryService}).
 *
 * Hosts supply only what genuinely diverges by schema/business:
 * - {@see baseQuery()} — the model query already scoped to valid coupons.
 * - {@see hasUserUsed()} — order lookup differs (coupon code vs foreign key).
 * - optionally {@see isAvailable()}, {@see attributesFor()}, {@see couponTypeFor()}.
 */
abstract class AbstractCouponRepository implements CouponRepositoryInterface
{
    public function __construct(
        protected ?CouponDataFactory $couponDataFactory = null,
        protected ?CouponInventoryService $couponInventory = null,
    ) {
    }

    /**
     * Host model query already scoped to currently valid coupons
     * (e.g. Coupon::query()->valid()).
     *
     * @return Builder<Model>
     */
    abstract protected function baseQuery(): Builder;

    abstract public function hasUserUsed(string $code, int $userId): bool;

    public function findActiveByCode(string $code, CouponKind $kind): ?CouponData
    {
        $coupon = $this->baseQuery()
            ->where($this->codeColumn(), $code)
            ->where($this->typeColumn(), $this->couponTypeFor($kind))
            ->first();

        if (! $coupon instanceof Model || ! $this->isAvailable($coupon, $kind)) {
            return null;
        }

        /** @var CouponData $couponData */
        $couponData = $this->couponDataFactory()->fromCoupon(
            coupon: $coupon,
            kind: $kind,
            couponDataClass: CouponData::class,
            status: true,
            attributes: $this->attributesFor($coupon),
        );

        return $couponData;
    }

    public function decrementInventory(string $code): bool
    {
        $coupon = $this->baseQuery()
            ->where($this->codeColumn(), $code)
            ->where($this->typeColumn(), $this->couponTypeFor(CouponKind::Promotion))
            ->first();

        if (! $coupon instanceof Model) {
            return false;
        }

        return $this->couponInventory()->reserve(
            coupon: $coupon,
            decrement: fn (mixed $reserved): int => $this->decrementLeftQty($reserved),
        );
    }

    protected function decrementLeftQty(mixed $coupon): int
    {
        if (! $coupon instanceof Model) {
            return 0;
        }

        return $coupon->newQuery()
            ->whereKey($coupon->getKey())
            ->where($this->leftQtyColumn(), '>', 0)
            ->decrement($this->leftQtyColumn());
    }

    /**
     * Attributes carried on the CouponData (must include the coupon id for tracing).
     *
     * @return array<string, mixed>
     */
    protected function attributesFor(Model $coupon): array
    {
        return [
            'coupon_id' => $coupon->getKey(),
            'title'     => data_get($coupon, 'title'),
        ];
    }

    protected function isAvailable(Model $coupon, CouponKind $kind): bool
    {
        return true;
    }

    protected function couponTypeFor(CouponKind $kind): int|string
    {
        return $kind === CouponKind::Member ? 1 : 2;
    }

    protected function codeColumn(): string
    {
        return 'code';
    }

    protected function typeColumn(): string
    {
        return 'type';
    }

    protected function leftQtyColumn(): string
    {
        return 'left_qty';
    }

    protected function couponDataFactory(): CouponDataFactory
    {
        return $this->couponDataFactory ??= app(CouponDataFactory::class);
    }

    protected function couponInventory(): CouponInventoryService
    {
        return $this->couponInventory ??= app(CouponInventoryService::class);
    }
}
