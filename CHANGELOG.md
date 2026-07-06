# Changelog

All notable changes to `lalalili/commerce-kit` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.7.1] - 2026-07-07

### Fixed

- `AbstractCouponRepository`:`couponTypeFor` 支援 `FreeShipping`(type 3);
  `decrementInventory` 改以 promotion + free_shipping 兩種 type 尋券(member 券無庫存概念)。

## [0.7.0] - 2026-07-07

### Added

- `CouponCartConditionFactory` 支援免運券 `CouponKind::FreeShipping`
  (顯示名 translation key `cruds.coupon.free_shipping`,可由
  `commerce-kit.coupon_condition.names.free_shipping` 覆寫);搭配
  discount v3.4.0 + commerce-core v1.71.0。

## [0.6.0] - 2026-07-07

### Changed(效能,行為不變)

- `AbstractCartPromotionRefreshInputBuilder::lines()` 加入 memo:
  `promotionRefreshSignature()` / `promotionVersion()` / `build()` 共用同一份
  lines 解析結果,line resolver 與 attribute normalizer 由每次 refresh ≥3 次
  降為 1 次(`InputBuilderLinesMemoTest` 以 spy resolver 斷言)。
- 新增 `PromotionRefreshBenchmarkTest`(`@group benchmark`):100 lines × 6
  promotions 的 signature/refresh 效能邊界煙霧測試,防 O(L²×E) 級回歸。

## [0.5.0] - 2026-07-06

### Added

- `Cart\CartManager`(scoped):統一的 host 端購物車入口。以
  `commerce-kit.cart_manager.bindings` 宣告邏輯名稱 → container binding 對映
  (cptw `shopping_cart`、aitehub `cart`),提供 `cart()` / `checkout()` /
  `instance()` / `refreshDiscounts()`,消除 host 專屬 binding 字串散落。
- `Cart\Concerns\WireableCondition` trait:CartCondition 子類的 Livewire 序列化
  實作(host 自行 implements `Livewire\Wireable` 並掛 trait);kit 不依賴 livewire,
  Inertia host 直接用 base CartCondition。

### Notes

- 搭配 laravelshoppingcart v14.3.0 的 `rounding.per_condition_step`,host 的
  `CptwCart::getSubTotal()/getTotal()` 覆寫可整段移除,取回 base Cart 的
  totalsCache 與 pipeline 去重。

## [0.3.8] - 2026-06-27

### Added

- `Recurring\RecurringCheckoutContextBuilder` — config-driven glue mapping a billing cycle to the
  ECPay recurring (Credit Period) parameters consumed by commerce-payment's
  `RecurringPaymentGateway::startRecurring()`. Adds `recurring.cycles` config
  (monthly → M/1/999, yearly → Y/1/99) and registers the builder as a singleton.

## [0.3.7] - 2026-06-26

### Changed

- Added a new-host installation checklist that documents required
  `commerce-kit` config values, config-cache-safe callable rules, and the
  remaining host-owned `commerce-core` checkout bindings.

## [0.3.6] - 2026-06-26

### Changed

- Documented the current extraction boundary between `commerce-kit` and host
  checkout/coupon adapters so the remaining host-owned glue is not forced into
  config-only abstractions before cptw/aitehub behavior converges.

## [0.3.1] - 2026-06-26

### Changed

- `AbstractCouponRepository` is now generic (`@template TModel of Model`) so host
  subclasses can type `baseQuery()` against their own coupon model under phpstan.

## [0.3.0] - 2026-06-26

### Added

- `Coupons\AbstractCouponRepository` — config-light base implementing the
  discount `CouponRepositoryInterface`. Consolidates the host plumbing for
  turning a coupon model into a `CouponData` (commerce-core `CouponDataFactory`)
  and reserving promotion inventory (`CouponInventoryService`); hosts supply
  only the divergent `baseQuery()` / `hasUserUsed()` (plus optional guards).

## [0.2.0] - 2026-06-26

### Added

- `Coupons\CouponCartConditionFactory` — builds the applied-coupon cart
  condition via the commerce-core payload builder, with a config-driven
  condition class and translation-key-driven display names.

## [0.1.0] - 2026-06-26

### Added

- Package skeleton (service provider, config, CI/release workflows, test harness).
- `Contracts\CartDiscountRefresher` — host cart-service seam for the upcoming
  discount-refresh pipeline.
- `config/commerce-kit.php` with config-driven cart class, discount-refresh
  instance names, and coupon condition class/names.
- `Pipelines\CartDiscountRefreshPipeline` — config-driven cart pipeline that
  recomputes promotion/coupon conditions via the host-bound refresher.
