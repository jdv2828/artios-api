<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests\Service;

use App\Application\Service\DTO\CreateServiceDTO;
use Illuminate\Foundation\Http\FormRequest;

final class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function toDto(): CreateServiceDTO
    {
        $validated = $this->validated();

        return new CreateServiceDTO(
            name: (string) $validated['name'],
            durationMinutes: (int) $validated['duration_minutes'],
            price: $validated['price'],
        );
    }
}
