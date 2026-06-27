<?php

declare(strict_types=1);

use Lalalili\CommerceKit\Recurring\RecurringCheckoutContextBuilder;

it('maps monthly cycle to ECPay period params and defaults period_amount to amount', function (): void {
    $builder = app(RecurringCheckoutContextBuilder::class);

    $context = $builder->build([
        'order_number'      => 'SUB0001',
        'amount'            => 300,
        'item_name'         => '訂閱方案',
        'return_url'        => 'https://host.test/notify',
        'period_return_url' => 'https://host.test/period',
    ], 'monthly');

    expect($context['period_type'])->toBe('M')
        ->and($context['frequency'])->toBe(1)
        ->and($context['exec_times'])->toBe(999)
        ->and($context['period_amount'])->toBe(300)
        ->and($context['order_number'])->toBe('SUB0001')
        ->and($context['period_return_url'])->toBe('https://host.test/period');
});

it('maps yearly cycle to yearly period params', function (): void {
    $context = app(RecurringCheckoutContextBuilder::class)->build([
        'order_number' => 'SUB0002', 'amount' => 3000,
    ], 'yearly');

    expect($context['period_type'])->toBe('Y')
        ->and($context['exec_times'])->toBe(99);
});

it('respects an explicit period_amount override', function (): void {
    $context = app(RecurringCheckoutContextBuilder::class)->build([
        'order_number' => 'SUB0003', 'amount' => 300, 'period_amount' => 250,
    ], 'monthly');

    expect($context['period_amount'])->toBe(250);
});

it('throws when the cycle is not configured', function (): void {
    app(RecurringCheckoutContextBuilder::class)->build(['amount' => 100], 'weekly');
})->throws(InvalidArgumentException::class);
