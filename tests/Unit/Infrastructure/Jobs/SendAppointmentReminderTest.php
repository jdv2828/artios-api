<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Jobs;

use App\Infrastructure\Jobs\SendAppointmentReminder;
use App\Infrastructure\Mail\AppointmentNotificationMail;
use App\Infrastructure\Persistence\Eloquent\Models\AppointmentModel;
use App\Infrastructure\Persistence\Eloquent\Models\ClientModel;
use App\Infrastructure\Persistence\Eloquent\Models\ServiceModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

final class SendAppointmentReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_the_same_confirmation_link_used_by_the_confirmation_email(): void
    {
        Mail::fake();

        $service = $this->createService();
        $client = ClientModel::query()->create([
            'first_name' => 'Ana',
            'last_name' => 'Perez',
            'dni' => '30123456',
            'phone' => '+54 11 5555-8888',
            'email' => 'ana@example.com',
        ]);

        $appointment = AppointmentModel::query()->create([
            'client_id' => $client->id,
            'service_id' => $service->id,
            'scheduled_at' => now()->addDay(),
            'status' => 'scheduled',
            'confirmation_token' => 'token-123',
            'remind_1_day_before' => true,
            'remind_30_mins_before' => false,
            'remind_1_hour_before' => true,
        ]);

        (new SendAppointmentReminder($appointment->id, '1_day'))->handle();

        Mail::assertSent(AppointmentNotificationMail::class, function (AppointmentNotificationMail $mail): bool {
            self::assertSame('reminder', $mail->kind);
            self::assertStringContainsString('/confirmacion?token=token-123', $mail->confirmationUrl);

            return true;
        });
    }

    public function test_it_skips_completed_appointments(): void
    {
        Mail::fake();

        $service = $this->createService();
        $client = ClientModel::query()->create([
            'first_name' => 'Ana',
            'last_name' => 'Perez',
            'dni' => '30123456',
            'phone' => '+54 11 5555-8888',
            'email' => 'ana@example.com',
        ]);

        $appointment = AppointmentModel::query()->create([
            'client_id' => $client->id,
            'service_id' => $service->id,
            'scheduled_at' => now()->addDay(),
            'status' => 'completed',
            'confirmation_token' => 'token-123',
            'remind_1_day_before' => true,
            'remind_30_mins_before' => false,
            'remind_1_hour_before' => true,
        ]);

        (new SendAppointmentReminder($appointment->id, '1_day'))->handle();

        Mail::assertNothingSent();
    }

    private function createService(): ServiceModel
    {
        return ServiceModel::query()->create([
            'name' => 'Consulta general',
            'duration_minutes' => 30,
            'price' => 1000,
            'active' => true,
        ]);
    }
}
