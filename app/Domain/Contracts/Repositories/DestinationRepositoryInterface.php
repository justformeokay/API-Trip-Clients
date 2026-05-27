<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Repositories;

interface DestinationRepositoryInterface
{
    public function findById(int $id): ?array;
    public function findAll(int $page, int $limit): array;
    public function findPopular(int $limit): array;
    public function create(array $data): array;
    public function update(int $id, array $data): bool;
}
