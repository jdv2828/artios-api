<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Database\Factories\ClientModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ClientModel extends Model
{
    use HasFactory;

    protected $table = 'clients';

    protected $fillable = [
        'first_name',
        'last_name',
        'dni',
        'phone',
        'email',
    ];

    public function appointments(): HasMany
    {
        return $this->hasMany(AppointmentModel::class, 'client_id');
    }

    protected static function newFactory(): ClientModelFactory
    {
        return ClientModelFactory::new();
    }
}
