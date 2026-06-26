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
- `Support\ConfiguredCheckoutOrderBuilder` — config-driven implementation of
  commerce-core's `CheckoutOrderBuilder`; hosts describe where checkout cart
  lines live (`getContent()->all()` vs `cartContent->all()`) and whether item
  attributes should be normalized, removing one-off checkout order builder
  classes while keeping cart completion in the host. (`0.3.5`)
- `config/commerce-kit.php` — config-driven integration knobs (cart class,
  discount-refresh instance names/callable, checkout order line source, coupon
  condition class/names) so host schema and naming differences (e.g. cptw
  `shopping_cart`/`checkout` vs aitehub `cart`/`checkout`) are absorbed without
  forking the glue.

**Deliberately left in the host:** `CouponCheckoutAdapter`,
`CheckoutCartAccessor`, and the order coupon lifecycle still diverge between
hosts (cart completion, trace strategy, coupon clearing behavior) and are **not**
current consolidation targets — convergence should be driven by a real new
project, not forced.

## Extraction boundary

`commerce-kit` should absorb glue only when the host variation is data/config,
not business behavior. The current rule of thumb:

| Candidate | Current status | Reason |
| --- | --- | --- |
| Cart discount refresh | Extracted | Hosts differ by cart instance names and the refresh callable only. |
| Coupon cart condition | Extracted | Hosts differ by condition class and display names only. |
| Coupon repository plumbing | Extracted | Hosts keep query/user-usage guards while shared data/inventory mapping lives here. |
| Promotion refresh input builder | Extracted | Hosts keep promotion lookup/mapping while shared cart-line/fingerprint logic lives here. |
| Checkout order builder | Extracted | Hosts differ by where checkout line items live and whether item attributes need normalization. |
| Checkout cart accessor | Host-owned | Hosts still differ in cart service construction, session/checkout cleanup order, and post-checkout refresh behavior. |
| Coupon checkout adapter | Host-owned | Hosts still differ in coupon service signatures, order-total handling, trace clearing, and condition removal behavior. |
| Order coupon lifecycle | Host-owned | Reservation/release timing is still tied to host order/payment policies. |

Revisit the host-owned rows only after either:

- a new commerce project can use the same behavior without host callables; or
- cptw and aitehub first converge their own service contracts so the remaining
  difference becomes configuration instead of custom lifecycle code.

## Install in a new host app

Install the package from the tagged VCS repository, then publish the config:

```bash
composer require lalalili/commerce-kit:^0.3
php artisan vendor:publish --tag=commerce-kit-config
```

Laravel auto-discovers `CommerceKitServiceProvider`, so a new host does not need
to manually register the provider. The host must still configure the integration
points that are genuinely application-specific:

1. Set `commerce-kit.cart_class` to the host cart class.
2. Set `commerce-kit.discount_refresh.instances` and
   `commerce-kit.discount_refresh.checkout_instance` to the cart instance names
   used by the host.
3. Set `commerce-kit.discount_refresh.refresher` to a config-cache-safe callable,
   such as `[App\Services\CartService::class, 'refreshDiscountConditions']`.
4. Set `commerce-kit.checkout_order.items` to the checkout line source:
   `method=getContent` / `collection_method=all` for cart objects, or
   `property=cartContent` / `collection_method=all` for cart service wrappers.
5. Set `commerce-kit.checkout_order.normalize_item_attributes=true` when cart
   lines expose attributes in host-specific wrappers instead of the normalized
   commerce-core shape.
6. Set `commerce-kit.coupon_condition.class` when the host needs a custom
   `CartCondition` implementation, such as a Livewire `Wireable` condition.

Do not put closures in `config/commerce-kit.php`; Laravel cannot serialize them
when the host runs `config:cache`.

The host still owns these `commerce-core` bindings:

```php
use App\Services\Commerce\HostCheckoutCartAccessor;
use App\Services\Commerce\HostCouponCheckoutAdapter;
use Lalalili\CommerceCore\Contracts\CheckoutCartAccessor;
use Lalalili\CommerceCore\Contracts\CouponCheckoutAdapter;

$this->app->singleton(CheckoutCartAccessor::class, HostCheckoutCartAccessor::class);
$this->app->singleton(CouponCheckoutAdapter::class, HostCouponCheckoutAdapter::class);
```

Those two classes are intentionally host-owned until cart completion and coupon
clearing semantics converge across real projects. `CheckoutOrderBuilder` is
provided by `commerce-kit` unless the host has a more specific implementation and
binds it before the kit's `singletonIf` runs.

Install `lalalili/commerce-payment` separately when the host needs gateway,
invoice, refund, or payment reconciliation features.

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
| `checkout_order.expected_cart_class` | Optional checkout cart class override; defaults to `cart_class`. |
| `checkout_order.items.method` | Method used to read checkout cart content, e.g. `getContent`. |
| `checkout_order.items.property` | Property path used when items live on a cart service, e.g. `cartContent`. |
| `checkout_order.items.collection_method` | Optional method called on the resolved item collection, e.g. `all`. |
| `checkout_order.normalize_item_attributes` | Whether to normalize item attributes through commerce-core before building order data. |
| `coupon_condition.class` | `CartCondition` class produced by the coupon condition factory. |
| `coupon_condition.names` | Display names per coupon kind (`member` / `promotion`). |

## Development

```bash
composer install
composer test       # pest
composer analyse    # phpstan level 8
composer format     # pint
```
