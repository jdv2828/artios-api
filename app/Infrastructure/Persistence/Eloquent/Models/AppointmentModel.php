<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Database\Factories\AppointmentModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

final class AppointmentModel extends Model
{
    use HasFactory;

    protected $table = 'appointments';

    protected $fillable = [
        'client_id',
        'employee_id',
        'service_id',
        'scheduled_at',
        'status',
        'confirmation_token',
        'remind_1_day_before',
        'remind_30_mins_before',
        'remind_1_hour_before',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'remind_1_day_before' => 'boolean',
        'remind_30_mins_before' => 'boolean',
        'remind_1_hour_before' => 'boolean',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(ClientModel::class, 'client_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(ServiceModel::class, 'service_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    protected static function newFactory(): AppointmentModelFactory
    {
        return AppointmentModelFactory::new();
    }
}
