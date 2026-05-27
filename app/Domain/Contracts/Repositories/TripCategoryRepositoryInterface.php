<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Repositories;

interface TripCategoryRepositoryInterface
{
    public function findAll(): array;
    public function findById(int $id): ?array;
    public function create(array $data): array;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
}
