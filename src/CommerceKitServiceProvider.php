<?php

declare(strict_types=1);

namespace Lalalili\CommerceKit;

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
}
