<?php

declare(strict_types=1);

namespace App\Domain\Entities;

/**
 * User domain entity.
 */
final class User
{
    public function __construct(
        public readonly ?int    $id,
        public readonly string  $name,
        public readonly string  $email,
        public readonly string  $passwordHash,
        public readonly string  $role,
        public readonly ?string $phone,
        public readonly ?string $avatarUrl,
        public readonly bool    $emailVerified,
        public readonly bool    $isActive,
        public readonly ?string $emailVerifiedAt,
        public readonly ?string $deletedAt,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id:              isset($data['id'])   ? (int) $data['id'] : null,
            name:            $data['name']         ?? '',
            email:           $data['email']        ?? '',
            passwordHash:    $data['password']     ?? '',
            role:            $data['role']         ?? 'user',
            phone:           $data['phone']        ?? null,
            avatarUrl:       $data['avatar_url']   ?? null,
            emailVerified:   (bool) ($data['email_verified']    ?? false),
            isActive:        (bool) ($data['is_active']         ?? true),
            emailVerifiedAt: $data['email_verified_at']         ?? null,
            deletedAt:       $data['deleted_at']                ?? null,
            createdAt:       $data['created_at']                ?? null,
            updatedAt:       $data['updated_at']                ?? null,
        );
    }

    public function toPublicArray(): array
    {
        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'email'            => $this->email,
            'role'             => $this->role,
            'phone'            => $this->phone,
            'avatar_url'       => $this->avatarUrl,
            'email_verified'   => $this->emailVerified,
            'is_active'        => $this->isActive,
            'created_at'       => $this->createdAt,
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isOrganizer(): bool
    {
        return in_array($this->role, ['organizer', 'admin'], true);
    }
}
