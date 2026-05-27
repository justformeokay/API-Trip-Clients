<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Helpers\ResponseHelper;
use App\Services\AuthService;
use App\Validators\AuthValidator;

/**
 * Authentication controller.
 * Handles: register, login, refresh, logout, forgot/reset password, email verify.
 */
final class AuthController
{
    public function __construct(
        private readonly AuthService    $authService,
        private readonly AuthValidator  $validator
    ) {}

    /**
     * POST /api/v1/auth/register
     */
    public function register(Request $request): Response
    {
        $data = $request->all();
        $this->validator->validateRegister($data);

        $result = $this->authService->register($data);

        return ResponseHelper::created($result, 'Registration successful. Please check your email to verify your account.');
    }

    /**
     * POST /api/v1/auth/login
     */
    public function login(Request $request): Response
    {
        $data = $request->all();
        $this->validator->validateLogin($data);

        $result = $this->authService->login($data, $request->getIp(), $request->getUserAgent());

        return ResponseHelper::success($result, 'Login successful.');
    }

    /**
     * POST /api/v1/auth/refresh
     */
    public function refresh(Request $request): Response
    {
        $data = $request->all();
        $this->validator->validateRefreshToken($data);

        $result = $this->authService->refreshTokens(
            $data['refresh_token'],
            $request->getIp(),
            $request->getUserAgent()
        );

        return ResponseHelper::success($result, 'Token refreshed successfully.');
    }

    /**
     * POST /api/v1/auth/logout
     */
    public function logout(Request $request): Response
    {
        $data = $request->all();

        // Refresh token in body is optional; if missing, revoke the session by access token payload
        if (!empty($data['refresh_token'])) {
            $this->authService->logout(
                $data['refresh_token'],
                (int) $request->getAttribute('user_id')
            );
        } else {
            $this->authService->logoutAll((int) $request->getAttribute('user_id'));
        }

        return ResponseHelper::success(null, 'Logged out successfully.');
    }

    /**
     * POST /api/v1/auth/logout-all  (revoke all devices)
     */
    public function logoutAll(Request $request): Response
    {
        $this->authService->logoutAll((int) $request->getAttribute('user_id'));
        return ResponseHelper::success(null, 'Logged out from all devices.');
    }

    /**
     * POST /api/v1/auth/forgot-password
     */
    public function forgotPassword(Request $request): Response
    {
        $data = $request->all();
        $this->validator->validateForgotPassword($data);
        $this->authService->forgotPassword($data['email']);
        // Always return success to prevent email enumeration
        return ResponseHelper::success(null, 'If your email is registered, you will receive a password reset link.');
    }

    /**
     * POST /api/v1/auth/reset-password
     */
    public function resetPassword(Request $request): Response
    {
        $data = $request->all();
        $this->validator->validateResetPassword($data);
        $this->authService->resetPassword($data['token'], $data['password']);
        return ResponseHelper::success(null, 'Password reset successfully. Please log in.');
    }

    /**
     * GET /api/v1/auth/verify-email?token={token}
     */
    public function verifyEmail(Request $request): Response
    {
        $token = $request->query('token', '');

        if (empty($token)) {
            return ResponseHelper::error('Verification token is required.', 422);
        }

        $this->authService->verifyEmail($token);
        return ResponseHelper::success(null, 'Email verified successfully.');
    }
}
