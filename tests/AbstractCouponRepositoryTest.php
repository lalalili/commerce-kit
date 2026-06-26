<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lalalili\CommerceKit\Coupons\AbstractCouponRepository;
use Lalalili\Discount\DTOs\CouponData;
use Lalalili\Discount\Enums\CouponAmountMode;
use Lalalili\Discount\Enums\CouponKind;

beforeEach(function (): void {
    Schema::create('kit_coupons', function (Blueprint $table): void {
        $table->id();
        $table->string('code');
        $table->unsignedTinyInteger('type');
        $table->unsignedTinyInteger('scope')->default(0);
        $table->decimal('trigger_amount')->nullable();
        $table->decimal('amount')->default(0);
        $table->string('amount_mode')->nullable();
        $table->unsignedInteger('limit_qty')->nullable();
        $table->unsignedInteger('left_qty')->nullable();
        $table->unsignedBigInteger('user_id')->nullable();
        $table->string('title')->nullable();
        $table->timestamps();
    });
});

it('builds CouponData from a host coupon via the commerce-core factory', function (): void {
    KitTestCoupon::create([
        'code'           => 'PROMO10',
        'type'           => 2,
        'scope'          => 2,
        'trigger_amount' => 500,
        'amount'         => 75.5,
        'amount_mode'    => 'fixed',
        'limit_qty'      => 10,
        'left_qty'       => 10,
        'title'          => 'Spring sale',
    ]);

    $data = (new KitTestCouponRepository())->findActiveByCode('PROMO10', CouponKind::Promotion);

    expect($data)->toBeInstanceOf(CouponData::class);
    expect($data->code)->toBe('PROMO10');
    expect($data->kind)->toBe(CouponKind::Promotion);
    expect($data->scope)->toBe(2);
    expect($data->amount)->toBe(75.5);
    expect($data->amountMode)->toBe(CouponAmountMode::Fixed);
    expect($data->status)->toBeTrue();
    expect($data->attributes['coupon_id'])->toBe(1);
    expect($data->attributes['title'])->toBe('Spring sale');
});

it('returns null when no coupon matches the code and kind', function (): void {
    KitTestCoupon::create(['code' => 'MEMBER1', 'type' => 1, 'amount' => 50]);

    // MEMBER1 is a member coupon, so a promotion lookup misses it.
    expect((new KitTestCouponRepository())->findActiveByCode('MEMBER1', CouponKind::Promotion))->toBeNull();
    expect((new KitTestCouponRepository())->findActiveByCode('NOPE', CouponKind::Member))->toBeNull();
});

it('honours an isAvailable() guard override', function (): void {
    KitTestCoupon::create(['code' => 'BLOCKED', 'type' => 2, 'amount' => 10, 'limit_qty' => 5, 'left_qty' => 5]);

    expect((new KitGuardedCouponRepository())->findActiveByCode('BLOCKED', CouponKind::Promotion))->toBeNull();
});

it('decrements promotion inventory and no-ops when untracked', function (): void {
    KitTestCoupon::create(['code' => 'TRACKED', 'type' => 2, 'amount' => 10, 'limit_qty' => 3, 'left_qty' => 3]);
    KitTestCoupon::create(['code' => 'UNTRACKED', 'type' => 2, 'amount' => 10]);

    $repo = new KitTestCouponRepository();

    expect($repo->decrementInventory('TRACKED'))->toBeTrue();
    expect(KitTestCoupon::where('code', 'TRACKED')->value('left_qty'))->toBe(2);

    // limit_qty null => inventory is not tracked, reserve succeeds without touching rows.
    expect($repo->decrementInventory('UNTRACKED'))->toBeTrue();
    expect($repo->decrementInventory('MISSING'))->toBeFalse();
});

/**
 * @property int $id
 */
class KitTestCoupon extends Model
{
    protected $table = 'kit_coupons';

    protected $guarded = [];
}

class KitTestCouponRepository extends AbstractCouponRepository
{
    protected function baseQuery(): Builder
    {
        return KitTestCoupon::query();
    }

    public function hasUserUsed(string $code, int $userId): bool
    {
        return false;
    }
}

class KitGuardedCouponRepository extends KitTestCouponRepository
{
    protected function isAvailable(Model $coupon, CouponKind $kind): bool
    {
        return false;
    }
}
