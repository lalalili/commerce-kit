<?php

declare(strict_types=1);

use Lalalili\CommerceKit\Cart\Concerns\WireableCondition;
use Lalalili\ShoppingCart\CartCondition;

final class WireableStubCondition extends CartCondition
{
    use WireableCondition;
}

it('round-trips condition state through toLivewire/fromLivewire', function (): void {
    $condition = new WireableStubCondition([
        'name'   => '會員九折券',
        'type'   => 'member_coupon',
        'target' => 'total',
        'value'  => '-10%',
        'order'  => 3,
    ], 289.0);

    $payload = $condition->toLivewire();
    $restored = WireableStubCondition::fromLivewire($payload);

    expect($restored->getName())->toBe('會員九折券')
        ->and($restored->getType())->toBe('member_coupon')
        ->and($restored->getTarget())->toBe('total')
        ->and((string) $restored->getValue())->toBe('-10%')
        ->and($restored->getOrder())->toBe(3)
        ->and($restored->parsedRawValue)->toBe(289.0);
});

it('keeps order changes in the wireable payload', function (): void {
    $condition = new WireableStubCondition([
        'name'   => 'promo',
        'type'   => 'promotion_coupon',
        'target' => 'total',
        'value'  => '-50',
    ]);

    $condition->setOrder(7);

    $restored = WireableStubCondition::fromLivewire($condition->toLivewire());

    expect($restored->getOrder())->toBe(7);
});
