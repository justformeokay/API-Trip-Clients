<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Repositories;

interface OrganizerRepositoryInterface
{
    public function findById(int $id): ?array;
    public function findByUserId(int $userId): ?array;
    public function create(array $data): array;
    public function update(int $id, array $data): bool;
}
