# Changelog

All notable changes to `lalalili/commerce-kit` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
