<?php

declare(strict_types=1);

namespace Lalalili\CommerceKit;

use Lalalili\CommerceKit\Contracts\CartDiscountRefresher;
use Lalalili\CommerceKit\Coupons\CouponCartConditionFactory;
use Lalalili\CommerceKit\Pipelines\CartDiscountRefreshPipeline;
use Lalalili\CommerceKit\Recurring\RecurringCheckoutContextBuilder;
use Lalalili\CommerceKit\Support\ConfiguredCartDiscountRefresher;
use Lalalili\CommerceKit\Support\ConfiguredCheckoutOrderBuilder;
use Lalalili\CommerceCore\Contracts\CheckoutOrderBuilder;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CommerceKitServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('commerce-kit')
            ->hasConfigFile('commerce-kit');
    }

    public function registeringPackage(): void
    {
        $this->app->singletonIf(CartDiscountRefresher::class, ConfiguredCartDiscountRefresher::class);
        $this->app->singletonIf(CheckoutOrderBuilder::class, ConfiguredCheckoutOrderBuilder::class);
        $this->app->singleton(CouponCartConditionFactory::class);
        $this->app->singleton(CartDiscountRefreshPipeline::class);
        $this->app->singleton(RecurringCheckoutContextBuilder::class);
    }
}
