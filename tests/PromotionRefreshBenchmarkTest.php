<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Lalalili\CommerceKit\Promotion\AbstractCartPromotionRefreshInputBuilder;
use Lalalili\Discount\Contexts\PromotionContext;
use Lalalili\Discount\Contexts\PromotionSet;
use Lalalili\Discount\Engines\DefaultCartPromotionRefreshService;

/**
 * 折扣刷新效能煙霧測試(量測基線,上限值取寬鬆邊界防回歸)。
 * 2026-07-07 實測參考(容器內):100 lines × 6 promos
 * signature ~ 個位數 ms、refresh ~ 數十 ms(倒排索引交集 + lines memo 後)。
 */
final class BenchmarkInputBuilder extends AbstractCartPromotionRefreshInputBuilder
{
    /**
     * @var array<int|string, PromotionSet>
     */
    public static array $sets = [];

    /**
     * @return array<int|string, PromotionSet>
     */
    protected function buildPromotionSetsByProductId(): array
    {
        return self::$sets;
    }
}

/**
 * @return array{0: Collection<int|string, mixed>, 1: Collection<int|string, mixed>}
 */
function benchmarkFixtures(int $lineCount, int $promotionsPerLine): array
{
    $content = [];
    $products = [];
    $sets = [];

    for ($i = 1; $i <= $lineCount; $i++) {
        $content[$i] = (object) [
            'id'              => $i,
            'quantity'        => ($i % 3) + 1,
            'price'           => 100.0 + $i,
            'associatedModel' => 'Product',
            'attributes'      => [],
        ];
        $products[$i] = (object) ['id' => $i];

        $promotions = [];
        for ($j = 1; $j <= $promotionsPerLine; $j++) {
            // 一半 group rebate(共享事件 id 池)、一半單品折扣
            $promotions[] = $j % 2 === 0
                ? new PromotionContext(type: 6, sort: $j, rebateGetAmount: 0.8, eventId: 9000 + $j, rebateTriggerAmount: 5, attributes: ['event_id' => 9000 + $j, 'type' => 6])
                : new PromotionContext(type: 1, sort: $j, discountAmount: 0.9, eventId: 1000 + ($i * 10) + $j, attributes: ['event_id' => 1000 + ($i * 10) + $j, 'type' => 1]);
        }
        $sets[$i] = new PromotionSet($promotions);
    }

    BenchmarkInputBuilder::$sets = $sets;

    return [new Collection($content), new Collection($products)];
}

it('100 lines × 6 promotions 的 signature 與 refresh 在效能邊界內', function (): void {
    [$content, $products] = benchmarkFixtures(100, 6);

    $builder = new BenchmarkInputBuilder(content: $content, products: $products);

    $start = hrtime(true);
    $builder->promotionRefreshSignature();
    $signatureMs = (hrtime(true) - $start) / 1e6;

    $input = $builder->build();

    $start = hrtime(true);
    $result = (new DefaultCartPromotionRefreshService())->refresh($input);
    $refreshMs = (hrtime(true) - $start) / 1e6;

    expect($result->metadata['line_count'])->toBe(100)
        // 寬鬆上限:防止 O(L²×E) 級回歸,非精確效能斷言
        ->and($signatureMs)->toBeLessThan(200.0)
        ->and($refreshMs)->toBeLessThan(1000.0);
})->group('benchmark');
