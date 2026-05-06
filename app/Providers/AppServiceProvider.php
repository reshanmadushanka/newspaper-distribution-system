<?php

namespace App\Providers;

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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
