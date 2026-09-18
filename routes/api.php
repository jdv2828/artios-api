<?php

declare(strict_types=1);

use App\Infrastructure\Http\Controllers\Auth\LoginController;
use App\Infrastructure\Http\Controllers\Auth\LogoutController;
use App\Infrastructure\Http\Controllers\Appointment\AppointmentController;
use App\Infrastructure\Http\Controllers\Appointment\ConfirmAppointmentController;
use App\Infrastructure\Http\Controllers\Appointment\ClientAppointmentsController;
use App\Infrastructure\Http\Controllers\Appointment\RescheduleController;
use App\Infrastructure\Http\Controllers\Appointment\ScheduleAppointmentController;
use App\Infrastructure\Http\Controllers\ServiceController;
use App\Infrastructure\Http\Controllers\PublicServiceController;
use App\Infrastructure\Http\Controllers\AvailabilityController;
use App\Infrastructure\Http\Controllers\Employee\EmployeeAvailabilityController;
use App\Infrastructure\Http\Controllers\Employee\EmployeeBlockedDatesController;
use App\Infrastructure\Http\Controllers\Employee\EmployeeController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', LoginController::class)
    ->middleware('throttle:5,1')
    ->name('auth.login');

Route::post('/auth/logout', LogoutController::class)
    ->middleware('auth:sanctum')
    ->name('auth.logout');

Route::post('/appointments', ScheduleAppointmentController::class)
    ->middleware('throttle:10,1')
    ->name('appointments.schedule');

Route::get('/appointments/confirm/{token}', ConfirmAppointmentController::class)
    ->middleware('throttle:10,1')
    ->name('appointments.confirm');

Route::get('/clients/{dni}/appointments', ClientAppointmentsController::class)
    ->middleware('throttle:10,1')
    ->name('clients.appointments');

Route::get('/services/active', PublicServiceController::class)
    ->middleware('throttle:30,1')
    ->name('services.active');

Route::get('/availability', AvailabilityController::class)
    ->middleware('throttle:30,1')
    ->name('availability.index');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
    Route::get('/employees/{id}/availability', [EmployeeAvailabilityController::class, 'show'])
        ->middleware('employee.self-or-admin')
        ->name('employees.availability.show');
    Route::put('/employees/{id}/availability', [EmployeeAvailabilityController::class, 'update'])
        ->middleware('employee.self-or-admin')
        ->name('employees.availability.update');
    Route::post('/employees/{id}/blocked-dates', [EmployeeBlockedDatesController::class, 'store'])
        ->middleware('employee.self-or-admin')
        ->name('employees.blocked_dates.store');
    Route::delete('/employees/{id}/blocked-dates/{date}', [EmployeeBlockedDatesController::class, 'destroy'])
        ->middleware('employee.self-or-admin')
        ->name('employees.blocked_dates.destroy');

    Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::get('/appointments/{id}', [AppointmentController::class, 'show'])->name('appointments.show');
    Route::patch('/appointments/{id}', [AppointmentController::class, 'update'])->name('appointments.update');
    Route::post('/appointments/{id}/reschedule', RescheduleController::class)
        ->middleware('role.admin')
        ->name('appointments.reschedule');

    Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
    Route::post('/services', [ServiceController::class, 'store'])
        ->middleware('role.admin')
        ->name('services.store');
    Route::get('/services/{id}', [ServiceController::class, 'show'])->name('services.show');
    Route::put('/services/{id}', [ServiceController::class, 'update'])
        ->middleware('role.admin')
        ->name('services.update');
    Route::delete('/services/{id}', [ServiceController::class, 'destroy'])
        ->middleware('role.admin')
        ->name('services.destroy');
});
