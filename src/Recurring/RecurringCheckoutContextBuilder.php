<?php

declare(strict_types=1);

namespace Lalalili\CommerceKit\Recurring;

use InvalidArgumentException;

/**
 * Config-driven glue that maps a billing cycle to the ECPay recurring (Credit Period)
 * parameters consumed by commerce-payment's RecurringPaymentGateway::startRecurring().
 *
 * Hosts only describe the neutral checkout input (order number, amount, URLs) and a cycle
 * string; this builder appends period_amount/period_type/frequency/exec_times from config.
 * It does not depend on any subscription domain, keeping commerce-kit pure integration glue.
 */
final class RecurringCheckoutContextBuilder
{
    /**
     * @param  array<string, mixed>  $base order_number、amount、item_name、return_url、period_return_url
     * @return array<string, mixed>
     */
    public function build(array $base, string $cycle): array
    {
        $params = $this->cycleParams($cycle);

        $amount = $base['amount'] ?? 0;

        return array_merge($base, [
            'period_amount' => $base['period_amount'] ?? $amount,
            'period_type'   => $params['period_type'],
            'frequency'     => $params['frequency'],
            'exec_times'    => $params['exec_times'],
        ]);
    }

    /**
     * @return array{period_type: string, frequency: int, exec_times: int}
     */
    private function cycleParams(string $cycle): array
    {
        /** @var array<string, array{period_type?: string, frequency?: int, exec_times?: int}> $cycles */
        $cycles = config('commerce-kit.recurring.cycles', []);

        if (! isset($cycles[$cycle])) {
            throw new InvalidArgumentException("commerce-kit.recurring.cycles has no entry for cycle [{$cycle}].");
        }

        $params = $cycles[$cycle];

        return [
            'period_type' => (string) ($params['period_type'] ?? 'M'),
            'frequency'   => (int) ($params['frequency'] ?? 1),
            'exec_times'  => (int) ($params['exec_times'] ?? 2),
        ];
    }
}
