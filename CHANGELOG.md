# Changelog

All notable changes to `lalalili/commerce-kit` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.1.0] - 2026-06-26

### Added

- Package skeleton (service provider, config, CI/release workflows, test harness).
- `Contracts\CartDiscountRefresher` — host cart-service seam for the upcoming
  discount-refresh pipeline.
- `config/commerce-kit.php` with config-driven cart class, discount-refresh
  instance names, and coupon condition class/names.
- `Pipelines\CartDiscountRefreshPipeline` — config-driven cart pipeline that
  recomputes promotion/coupon conditions via the host-bound refresher.
