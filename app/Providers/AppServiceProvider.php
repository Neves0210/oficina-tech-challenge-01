<?php

namespace App\Providers;

use App\Domain\Customers\CustomerRepository;
use App\Domain\Materials\MaterialRepository;
use App\Domain\Orders\BudgetRepository;
use App\Domain\Orders\CustomerAccessRepository;
use App\Domain\Orders\OrderRepository;
use App\Domain\Services\ServiceRepository;
use App\Domain\Vehicles\VehicleRepository;
use App\Infrastructure\Persistence\EloquentBudgetRepository;
use App\Infrastructure\Persistence\EloquentCustomerAccessRepository;
use App\Infrastructure\Persistence\EloquentCustomerRepository;
use App\Infrastructure\Persistence\EloquentMaterialRepository;
use App\Infrastructure\Persistence\EloquentOrderRepository;
use App\Infrastructure\Persistence\EloquentServiceRepository;
use App\Infrastructure\Persistence\EloquentVehicleRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CustomerRepository::class, EloquentCustomerRepository::class);
        $this->app->bind(MaterialRepository::class, EloquentMaterialRepository::class);
        $this->app->bind(OrderRepository::class, EloquentOrderRepository::class);
        $this->app->bind(BudgetRepository::class, EloquentBudgetRepository::class);
        $this->app->bind(CustomerAccessRepository::class, EloquentCustomerAccessRepository::class);
        $this->app->bind(VehicleRepository::class, EloquentVehicleRepository::class);
        $this->app->bind(ServiceRepository::class, EloquentServiceRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
