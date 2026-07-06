<?php

declare(strict_types=1);

namespace Lalalili\CommerceKit\Cart\Concerns;

/**
 * CartCondition 子類的 Livewire 序列化實作。
 *
 * 使用方式(host 端,需自行安裝 livewire/livewire):
 *
 *     class CptwCartCondition extends CartCondition implements \Livewire\Wireable
 *     {
 *         use \Lalalili\CommerceKit\Cart\Concerns\WireableCondition;
 *     }
 *
 * trait 本身不引用 Livewire 型別,commerce-kit 不需依賴 livewire;
 * 非 Livewire host(如 Inertia)直接使用 base CartCondition 即可。
 */
trait WireableCondition
{
    /**
     * @var array<string, mixed>
     */
    private array $wireableArgs;

    /**
     * @param array<string, mixed> $args (name, type, target, value, order…)
     */
    public function __construct(array $args, float|int $parsedRawValue = 0.0)
    {
        $this->wireableArgs = $args;

        parent::__construct($args);

        $this->parsedRawValue = (float) $parsedRawValue;
    }

    /**
     * @return array{parsedRawValue: float, args: array<string, mixed>}
     */
    public function toLivewire(): array
    {
        return [
            'parsedRawValue' => $this->parsedRawValue,
            'args'           => $this->wireableArgs,
        ];
    }

    /**
     * @param array{parsedRawValue: float|int, args: array<string, mixed>} $value
     */
    public static function fromLivewire($value): static
    {
        return new static($value['args'], $value['parsedRawValue']);
    }

    public function setOrder(int $order = 1): static
    {
        parent::setOrder($order);
        $this->wireableArgs['order'] = $order;

        return $this;
    }
}
