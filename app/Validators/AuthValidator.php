<?php

declare(strict_types=1);

namespace App\Validators;

final class AuthValidator extends BaseValidator
{
    public function validateRegister(array $data): void
    {
        $minPassword = (int) ($_ENV['PASSWORD_MIN_LENGTH'] ?? 8);

        $this->validate($data, [
            'name'                  => "required|string|min:2|max:100",
            'email'                 => "required|email|max:255",
            'password'              => "required|string|min:{$minPassword}|confirmed",
            'phone'                 => "nullable|string|max:20",
        ]);
    }

    public function validateLogin(array $data): void
    {
        $this->validate($data, [
            'email'    => 'required|email',
            'password' => 'required|string|min:1',
        ]);
    }

    public function validateRefreshToken(array $data): void
    {
        $this->validate($data, [
            'refresh_token' => 'required|string',
        ]);
    }

    public function validateForgotPassword(array $data): void
    {
        $this->validate($data, [
            'email' => 'required|email',
        ]);
    }

    public function validateResetPassword(array $data): void
    {
        $minPassword = (int) ($_ENV['PASSWORD_MIN_LENGTH'] ?? 8);

        $this->validate($data, [
            'token'    => 'required|string',
            'password' => "required|string|min:{$minPassword}|confirmed",
        ]);
    }
}
