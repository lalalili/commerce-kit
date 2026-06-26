<?php

declare(strict_types=1);

namespace Lalalili\CommerceKit\Coupons;

use Lalalili\CommerceCore\Services\CouponCartConditionPayloadBuilder;
use Lalalili\Discount\DTOs\PricingTraceEntry;
use Lalalili\Discount\Enums\CouponKind;
use Lalalili\ShoppingCart\CartCondition;

/**
 * Builds the cart condition that represents an applied coupon discount.
 *
 * The concrete condition class is config-driven (`commerce-kit.coupon_condition.class`)
 * so hosts can plug in their own Wireable condition; display names resolve from
 * `commerce-kit.coupon_condition.names` (translation keys), falling back to the
 * conventional `cruds.coupon.{member,promotion}` keys.
 */
class CouponCartConditionFactory
{
    public function __construct(private readonly CouponCartConditionPayloadBuilder $conditions)
    {
    }

    public function make(CouponKind $kind, int|float $discount, PricingTraceEntry $pricingTraceEntry): CartCondition
    {
        /** @var class-string<CartCondition> $conditionClass */
        $conditionClass = (string) config('commerce-kit.coupon_condition.class', CartCondition::class);

        return new $conditionClass($this->conditions->payload(
            kind: $kind->value,
            discount: $discount,
            pricingTraceEntry: $pricingTraceEntry->toArray(),
            name: $this->nameFor($kind),
        ));
    }

    public function typeFor(CouponKind $kind): string
    {
        return $this->conditions->typeFor($kind->value);
    }

    public function orderFor(CouponKind $kind): int
    {
        return $this->conditions->orderFor($kind->value);
    }

    private function nameFor(CouponKind $kind): string
    {
        $key = $kind === CouponKind::Member ? 'member' : 'promotion';
        $configured = config('commerce-kit.coupon_condition.names.'.$key);
        $translationKey = is_string($configured) && $configured !== '' ? $configured : 'cruds.coupon.'.$key;

        return (string) trans($translationKey);
    }
}
