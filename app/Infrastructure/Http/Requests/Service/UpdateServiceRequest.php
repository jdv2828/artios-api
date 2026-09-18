<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests\Service;

use App\Application\Service\DTO\UpdateServiceDTO;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateServiceRequest extends FormRequest
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
            'active' => ['required', 'boolean'],
        ];
    }

    public function toDto(int $id): UpdateServiceDTO
    {
        $validated = $this->validated();

        return new UpdateServiceDTO(
            id: $id,
            name: (string) $validated['name'],
            durationMinutes: (int) $validated['duration_minutes'],
            price: $validated['price'],
            active: (bool) $validated['active'],
        );
    }
}
