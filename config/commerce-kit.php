<?php

declare(strict_types=1);

use Lalalili\ShoppingCart\Cart;
use Lalalili\ShoppingCart\CartCondition;

return [
    /*
    |--------------------------------------------------------------------------
    | Cart integration
    |--------------------------------------------------------------------------
    |
    | The host cart class the integration glue expects (e.g. the host's
    | CptwCart). Pipelines and builders only act on instances of this class.
    |
    */
    'cart_class' => Cart::class,

    /*
    |--------------------------------------------------------------------------
    | Discount refresh
    |--------------------------------------------------------------------------
    |
    | Cart instance names the discount-refresh pipeline applies to, and the
    | instance name that is treated as "checkout" (which forces a refresh).
    | Hosts differ here: cptw uses shopping_cart/checkout, aitehub cart/checkout.
    |
    */
    'discount_refresh' => [
        'instances'         => ['shopping_cart', 'checkout'],
        'checkout_instance' => 'checkout',
        'refresher'         => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Checkout order builder
    |--------------------------------------------------------------------------
    |
    | Config-driven adapter for commerce-core's CheckoutOrderBuilder contract.
    | Hosts only describe where cart line items live; commerce-core still owns
    | transforming those lines into CheckoutOrderData.
    |
    */
    'checkout_order' => [
        'expected_cart_class'  => null,
        'invalid_cart_message' => null,
        'items'                => [
            'method'            => 'getContent',
            'property'          => null,
            'collection_method' => 'all',
        ],
        'normalize_item_attributes' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Coupon cart condition
    |--------------------------------------------------------------------------
    |
    | The CartCondition class produced by the coupon condition factory. Hosts
    | may point this at their own Wireable condition (e.g. CptwCartCondition).
    | Display names per coupon kind; null falls back to the kit defaults.
    |
    */
    'coupon_condition' => [
        'class' => CartCondition::class,
        'names' => [
            'member'    => null,
            'promotion' => null,
        ],
    ],
];
