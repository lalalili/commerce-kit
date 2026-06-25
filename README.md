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

## Status

Early skeleton (`0.x`). Currently ships:

- `Contracts\CartDiscountRefresher` — seam between the kit's discount-refresh
  pipeline and the host cart service.
- `config/commerce-kit.php` — config-driven integration knobs (cart class,
  discount-refresh instance names, coupon condition class/names) so host schema
  and naming differences (e.g. cptw `shopping_cart`/`checkout` vs aitehub
  `cart`/`checkout`) are absorbed without forking the glue.

Concrete glue (refresh pipeline, coupon condition factory, checkout adapters)
is being extracted from the host apps incrementally.

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
| `coupon_condition.class` | `CartCondition` class produced by the coupon condition factory. |
| `coupon_condition.names` | Display names per coupon kind (`member` / `promotion`). |

## Development

```bash
composer install
composer test       # pest
composer analyse    # phpstan level 8
composer format     # pint
```
