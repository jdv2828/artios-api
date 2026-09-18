<?php

namespace App\Models;

use App\Domain\User\UserRole;
use App\Infrastructure\Persistence\Eloquent\Models\AppointmentModel;
use App\Infrastructure\Persistence\Eloquent\Models\EmployeeBlockedDateModel;
use App\Infrastructure\Persistence\Eloquent\Models\EmployeeBreakModel;
use App\Infrastructure\Persistence\Eloquent\Models\EmployeeScheduleModel;
use App\Infrastructure\Persistence\Eloquent\Models\ServiceModel;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(ServiceModel::class, 'employee_services', 'user_id', 'service_id')
            ->withTimestamps();
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(EmployeeScheduleModel::class, 'user_id');
    }

    public function breaks(): HasMany
    {
        return $this->hasMany(EmployeeBreakModel::class, 'user_id');
    }

    public function blockedDates(): HasMany
    {
        return $this->hasMany(EmployeeBlockedDateModel::class, 'user_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(AppointmentModel::class, 'employee_id');
    }
}
