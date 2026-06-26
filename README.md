# lalalili/commerce-kit

Integration glue for the lalalili commerce package family. It wires the four
domain packages into a runnable checkout / discount / reconciliation flow so
host apps (and new commerce projects) stop re-implementing the same glue.

## Where it sits

```
laravelshoppingcart   cart storage + CartCondition + storage drivers
discount              stateless promotion/coupon calculation kernel
commerce-payment      ECPay / E.SUN gateways + invoice + refund + reconciliation
commerce-core         order/product/entitlement persistence + lifecycle + events
commerce-kit  ← here  integration seam binding all of the above together
host app              only host-specific concerns (points, subscriptions, ebook,
                      logistics, ERP, …)
```

`commerce-kit` depends on `commerce-core`, `discount`, and `laravelshoppingcart`;
`commerce-payment` is a `suggest` (the reconciliation seam already lives there).
There is no circular dependency: the kit sits on top, the host depends on the kit.

**Governance:** `commerce-core` intentionally does **not** depend on `discount`
(its coupon helpers stay decoupled via `mixed`/`class-string`). So any class that
**implements a `discount` contract** while reusing `commerce-core` helpers belongs
here in `commerce-kit`, not in `commerce-core`.

## What it ships (`0.x`)

- `Pipelines\CartDiscountRefreshPipeline` + `Contracts\CartDiscountRefresher` —
  config-driven cart pipeline that recomputes promotion/coupon conditions via a
  host-bound refresher. (`0.1.0`)
- `Coupons\CouponCartConditionFactory` — builds the applied-coupon cart condition
  via the commerce-core payload builder, with a config-driven condition class and
  translation-key-driven display names. (`0.2.0`)
- `Coupons\AbstractCouponRepository` — generic (`@template TModel of Model`) base
  implementing the `discount` `CouponRepositoryInterface`: turns a host coupon
  model into a `CouponData` (commerce-core `CouponDataFactory`) and reserves
  promotion inventory (`CouponInventoryService`). Hosts supply only the divergent
  `baseQuery()` / `hasUserUsed()` plus optional guards. (`0.3.0`)
- `Promotion\AbstractCartPromotionRefreshInputBuilder` — base implementation for
  `discount` cart-promotion refresh inputs: builds cart lines via commerce-core,
  caches promotion sets, and computes promotion refresh fingerprints. Hosts only
  supply the event / promotion lookup and mapping. (`0.3.3`)
- `Support\ConfiguredCartDiscountRefresher` — config-driven implementation of
  `CartDiscountRefresher`; points the kit pipeline at a host callable such as
  `[CartService::class, 'refreshDiscountConditions']`, removing the need for a
  one-off adapter class in each host. (`0.3.4`)
- `config/commerce-kit.php` — config-driven integration knobs (cart class,
  discount-refresh instance names/callable, coupon condition class/names) so host
  schema and naming differences (e.g. cptw `shopping_cart`/`checkout` vs aitehub
  `cart`/`checkout`) are absorbed without forking the glue.

**Deliberately left in the host:** the checkout adapters
(`CheckoutOrderBuilder` / `CouponCheckoutAdapter` / `CheckoutCartAccessor`) and
the order coupon lifecycle have diverged between hosts (cart architecture, schema,
trace strategy) and are **not** consolidation targets — convergence should be
driven by a real new project, not forced.

## Configuration

Publish and tune the config:

```bash
php artisan vendor:publish --tag=commerce-kit-config
```

| Key | Purpose |
| --- | --- |
| `cart_class` | Host cart class the glue acts on (e.g. `CptwCart`). |
| `discount_refresh.instances` | Cart instance names the refresh pipeline applies to. |
| `discount_refresh.checkout_instance` | Instance name treated as checkout (forces a refresh). |
| `discount_refresh.refresher` | Callable invoked by `ConfiguredCartDiscountRefresher`, e.g. `[CartService::class, 'refreshDiscountConditions']`. |
| `coupon_condition.class` | `CartCondition` class produced by the coupon condition factory. |
| `coupon_condition.names` | Display names per coupon kind (`member` / `promotion`). |

## Development

```bash
composer install
composer test       # pest
composer analyse    # phpstan level 8
composer format     # pint
```
