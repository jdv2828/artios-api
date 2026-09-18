<?php

namespace App\Providers;

use App\Application\Auth\Ports\AuthRepositoryInterface;
use App\Application\Appointment\Ports\AppointmentRepositoryInterface;
use App\Application\Appointment\Ports\ClientRepositoryInterface;
use App\Application\Availability\Ports\AvailabilityRepositoryInterface;
use App\Application\Service\Ports\ServiceRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Adapters\EloquentAuthRepository;
use App\Infrastructure\Persistence\Eloquent\Adapters\EloquentAppointmentRepository;
use App\Infrastructure\Persistence\Eloquent\Adapters\EloquentAvailabilityRepository;
use App\Infrastructure\Persistence\Eloquent\Adapters\EloquentClientRepository;
use App\Infrastructure\Persistence\Eloquent\Adapters\EloquentServiceRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AuthRepositoryInterface::class, EloquentAuthRepository::class);
        $this->app->bind(ClientRepositoryInterface::class, EloquentClientRepository::class);
        $this->app->bind(AppointmentRepositoryInterface::class, EloquentAppointmentRepository::class);
        $this->app->bind(ServiceRepositoryInterface::class, EloquentServiceRepository::class);
        $this->app->bind(AvailabilityRepositoryInterface::class, EloquentAvailabilityRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
