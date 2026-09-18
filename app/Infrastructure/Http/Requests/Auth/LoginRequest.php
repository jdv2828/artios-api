<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests\Auth;

use App\Application\Auth\DTO\LoginDTO;
use Illuminate\Foundation\Http\FormRequest;

final class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function toDto(): LoginDTO
    {
        $validated = $this->validated();

        return new LoginDTO(
            email: (string) $validated['email'],
            password: (string) $validated['password'],
        );
    }
}
