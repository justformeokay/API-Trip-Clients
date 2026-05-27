<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Domain\Contracts\Repositories\UserRepositoryInterface;
use App\Helpers\ResponseHelper;
use App\Exceptions\NotFoundException;
use App\Validators\BaseValidator;

/**
 * Authenticated user profile management.
 */
final class UserController
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    /**
     * GET /api/v1/me
     */
    public function profile(Request $request): Response
    {
        $userId = (int) $request->getAttribute('user_id');
        $user   = $this->userRepository->findById($userId);

        if (!$user) {
            throw new NotFoundException('User not found.');
        }

        return ResponseHelper::success($user->toPublicArray(), 'Profile fetched successfully.');
    }

    /**
     * PATCH /api/v1/me
     */
    public function updateProfile(Request $request): Response
    {
        $userId = (int) $request->getAttribute('user_id');
        $data   = $request->all();

        // Whitelist only safe, user-editable fields
        $allowed = array_intersect_key($data, array_flip(['name', 'phone']));

        if (!empty($allowed['name'])) {
            $allowed['name'] = trim((string) $allowed['name']);
            if (mb_strlen($allowed['name']) < 2 || mb_strlen($allowed['name']) > 100) {
                return ResponseHelper::error('Validation failed', 422, [
                    'name' => ['Name must be between 2 and 100 characters.'],
                ]);
            }
        }

        if (!empty($allowed)) {
            $this->userRepository->update($userId, $allowed);
        }

        $user = $this->userRepository->findById($userId);
        return ResponseHelper::success($user->toPublicArray(), 'Profile updated successfully.');
    }

    /**
     * POST /api/v1/me/change-password
     */
    public function changePassword(Request $request): Response
    {
        $userId  = (int) $request->getAttribute('user_id');
        $data    = $request->all();

        $errors = [];

        if (empty($data['current_password'])) {
            $errors['current_password'][] = 'Current password is required.';
        }
        if (empty($data['password'])) {
            $errors['password'][] = 'New password is required.';
        }
        if (strlen($data['password'] ?? '') < (int) ($_ENV['PASSWORD_MIN_LENGTH'] ?? 8)) {
            $errors['password'][] = 'New password must be at least ' . ($_ENV['PASSWORD_MIN_LENGTH'] ?? 8) . ' characters.';
        }
        if (($data['password'] ?? '') !== ($data['password_confirmation'] ?? '')) {
            $errors['password'][] = 'Password confirmation does not match.';
        }

        if (!empty($errors)) {
            return ResponseHelper::error('Validation failed.', 422, $errors);
        }

        $user = $this->userRepository->findById($userId);

        if (!$user || !password_verify($data['current_password'], $user->passwordHash)) {
            return ResponseHelper::error('Current password is incorrect.', 422, [
                'current_password' => ['Current password is incorrect.'],
            ]);
        }

        $this->userRepository->update($userId, [
            'password' => password_hash($data['password'], PASSWORD_ARGON2ID),
        ]);

        return ResponseHelper::success(null, 'Password changed successfully.');
    }
}
