<?php

declare(strict_types=1);

namespace Tests\Feature\Appointment;

use App\Infrastructure\Jobs\SendAppointmentReminder;
use App\Infrastructure\Persistence\Eloquent\Models\ServiceModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\CreatesEmployeeAvailability;
use Tests\TestCase;

final class AppointmentReminderDispatchTest extends TestCase
{
    use RefreshDatabase;
    use CreatesEmployeeAvailability;

    public function test_it_skips_past_reminder_delays(): void
    {
        Queue::fake();
        Mail::fake();

        $service = $this->createService();
        $scheduledAt = now()->addMinutes(60);

        $this->postJson('/api/appointments', [
            'first_name' => 'Ana',
            'last_name' => 'Perez',
            'dni' => '30123456',
            'phone' => '+54 11 5555-8888',
            'email' => 'ana@example.com',
            'service_id' => $service->id,
            'scheduled_at' => $scheduledAt->format('Y-m-d H:i:s'),
            'remind_1_day_before' => true,
            'remind_30_mins_before' => true,
        ])->assertCreated();

        Queue::assertPushed(SendAppointmentReminder::class, 1);
        Queue::assertPushed(SendAppointmentReminder::class, function (SendAppointmentReminder $job): bool {
            return $job->reminderType === '30_mins';
        });
    }

    private function createService(): ServiceModel
    {
        $service = ServiceModel::query()->create([
            'name' => 'Consulta general',
            'duration_minutes' => 30,
            'price' => 1000,
            'active' => true,
        ]);

        $this->createEmployeeWithServiceAndFullDaySchedule($service);

        return $service;
    }
}
