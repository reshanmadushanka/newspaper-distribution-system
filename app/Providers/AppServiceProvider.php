<?php

namespace App\Providers;

use App\Domain\Newspapers\Repositories\NewspaperRepository;
use App\Domain\Newspapers\Repositories\NewspaperRepositoryInterface;
use App\Domain\Pricing\Repositories\NewspaperPriceRepository;
use App\Domain\Pricing\Repositories\NewspaperPriceRepositoryInterface;
use App\Domain\Shops\Repositories\ShopRepository;
use App\Domain\Shops\Repositories\ShopRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ShopRepositoryInterface::class, ShopRepository::class);
        $this->app->bind(NewspaperRepositoryInterface::class, NewspaperRepository::class);
        $this->app->bind(NewspaperPriceRepositoryInterface::class, NewspaperPriceRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
