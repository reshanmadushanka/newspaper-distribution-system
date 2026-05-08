<?php

namespace App\Providers;

use App\Domain\Invoices\Repositories\InvoiceRepository;
use App\Domain\Invoices\Repositories\InvoiceRepositoryInterface;
use App\Domain\Newspapers\Repositories\NewspaperRepository;
use App\Domain\Newspapers\Repositories\NewspaperRepositoryInterface;
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
        $this->app->bind(InvoiceRepositoryInterface::class, InvoiceRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
