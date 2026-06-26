<?php

declare(strict_types=1);

namespace Lalalili\CommerceKit;

use Lalalili\CommerceKit\Coupons\CouponCartConditionFactory;
use Lalalili\CommerceKit\Pipelines\CartDiscountRefreshPipeline;
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
        $this->app->singleton(CouponCartConditionFactory::class);
        $this->app->singleton(CartDiscountRefreshPipeline::class);
    }
}
