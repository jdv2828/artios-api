<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Database\Factories\ServiceModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\User;

final class ServiceModel extends Model
{
    use HasFactory;

    protected $table = 'services';

    protected $fillable = [
        'name',
        'duration_minutes',
        'price',
        'active',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'price' => 'decimal:2',
        'active' => 'boolean',
    ];

    public function appointments(): HasMany
    {
        return $this->hasMany(AppointmentModel::class, 'service_id');
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'employee_services', 'service_id', 'user_id')
            ->withTimestamps();
    }

    protected static function newFactory(): ServiceModelFactory
    {
        return ServiceModelFactory::new();
    }
}
