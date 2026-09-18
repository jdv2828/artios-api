<?php

declare(strict_types=1);

namespace App\Providers;

use App\Infrastructure\Events\LaravelAppointmentConfirmed;
use App\Infrastructure\Events\LaravelAppointmentCancelled;
use App\Infrastructure\Events\LaravelAppointmentRescheduled;
use App\Infrastructure\Events\LaravelAppointmentScheduled;
use App\Infrastructure\Listeners\RescheduleAppointmentReminders;
use App\Infrastructure\Listeners\SendAppointmentCancelledEmail;
use App\Infrastructure\Listeners\SendAppointmentConfirmationEmail;
use App\Infrastructure\Listeners\SendRescheduleNotificationEmail;
use App\Infrastructure\Listeners\SendRescheduleNotificationToOldProfessional;
use App\Infrastructure\Listeners\ScheduleAppointmentReminders;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

final class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        LaravelAppointmentScheduled::class => [
            SendAppointmentConfirmationEmail::class,
            ScheduleAppointmentReminders::class,
        ],
        LaravelAppointmentConfirmed::class => [
            SendAppointmentConfirmationEmail::class,
        ],
        LaravelAppointmentCancelled::class => [
            SendAppointmentCancelledEmail::class,
        ],
        LaravelAppointmentRescheduled::class => [
            SendRescheduleNotificationEmail::class,
            SendRescheduleNotificationToOldProfessional::class,
            RescheduleAppointmentReminders::class,
        ],
    ];
}
