<?php

namespace App\Providers;

use App\Domain\Forecasting\Repositories\ForecastRepository;
use App\Domain\Forecasting\Repositories\ForecastRepositoryInterface;
use App\Domain\Invoices\Repositories\InvoiceRepository;
use App\Domain\Invoices\Repositories\InvoiceRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class DomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(InvoiceRepositoryInterface::class, InvoiceRepository::class);
        $this->app->bind(ForecastRepositoryInterface::class, ForecastRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
