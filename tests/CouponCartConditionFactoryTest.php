<?php

declare(strict_types=1);

use Lalalili\CommerceKit\Coupons\CouponCartConditionFactory;
use Lalalili\Discount\DTOs\PricingTraceEntry;
use Lalalili\Discount\Enums\CouponKind;
use Lalalili\ShoppingCart\CartCondition;

function traceEntry(): PricingTraceEntry
{
    return new PricingTraceEntry(
        stage: 'checkout',
        source: 'coupon',
        status: 'applied',
        scope: 'all',
        kind: 'member',
    );
}

it('builds a member coupon cart condition with the resolved type and name', function (): void {
    $factory = app(CouponCartConditionFactory::class);

    $condition = $factory->make(CouponKind::Member, 100, traceEntry());

    expect($condition)->toBeInstanceOf(CartCondition::class);
    expect($condition->getType())->toBe('member_coupon');
    // No translation registered in the package test app, so trans() returns the key.
    expect($condition->getName())->toBe('cruds.coupon.member');
    // Discount is stored as a negative adjustment on the cart total.
    expect((float) $condition->getValue())->toBe(-100.0);
});

it('exposes the condition type and order per coupon kind', function (): void {
    $factory = app(CouponCartConditionFactory::class);

    expect($factory->typeFor(CouponKind::Member))->toBe('member_coupon');
    expect($factory->typeFor(CouponKind::Promotion))->toBe('promotion_coupon');
    expect($factory->orderFor(CouponKind::Member))->toBeInt();
    expect($factory->orderFor(CouponKind::Promotion))->toBeInt();
});

it('honours a configured condition class', function (): void {
    config()->set('commerce-kit.coupon_condition.class', WireableTestCondition::class);

    $condition = app(CouponCartConditionFactory::class)->make(CouponKind::Promotion, 50, traceEntry());

    expect($condition)->toBeInstanceOf(WireableTestCondition::class);
    expect($condition->getType())->toBe('promotion_coupon');
});

final class WireableTestCondition extends CartCondition
{
}
