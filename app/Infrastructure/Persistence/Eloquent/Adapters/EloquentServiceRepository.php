<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Adapters;

use App\Application\Service\Ports\ServiceRepositoryInterface;
use App\Domain\Service\Service;
use App\Infrastructure\Persistence\Eloquent\Models\ServiceModel;
use RuntimeException;

final class EloquentServiceRepository implements ServiceRepositoryInterface
{
    public function findAll(): array
    {
        return ServiceModel::query()
            ->orderBy('name')
            ->get()
            ->map(fn (ServiceModel $model): Service => $this->toDomain($model))
            ->all();
    }

    public function findActive(): array
    {
        return ServiceModel::query()
            ->where('active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (ServiceModel $model): Service => $this->toDomain($model))
            ->all();
    }

    public function findById(int $id): ?Service
    {
        $model = ServiceModel::query()->find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function save(Service $service): Service
    {
        if ($service->id() === null) {
            $model = ServiceModel::query()->create([
                'name' => $service->name(),
                'duration_minutes' => $service->durationMinutes(),
                'price' => $service->price(),
                'active' => $service->active(),
            ]);

            $service->assignId((int) $model->id);

            return $service;
        }

        $model = ServiceModel::query()->find($service->id());

        if ($model === null) {
            throw new RuntimeException('Service not found while saving.');
        }

        $model->fill([
            'name' => $service->name(),
            'duration_minutes' => $service->durationMinutes(),
            'price' => $service->price(),
            'active' => $service->active(),
        ]);
        $model->save();

        return $service;
    }

    public function delete(int $id): void
    {
        ServiceModel::query()->whereKey($id)->delete();
    }

    private function toDomain(ServiceModel $model): Service
    {
        return new Service(
            id: (int) $model->id,
            name: (string) $model->name,
            durationMinutes: (int) $model->duration_minutes,
            price: (string) $model->price,
            active: (bool) $model->active,
        );
    }
}
