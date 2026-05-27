<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use App\Services\AuthService;
use App\Services\JwtService;
use App\Domain\Contracts\UserRepositoryInterface;
use App\Domain\Contracts\SessionRepositoryInterface;
use App\Domain\Entities\User;
use App\Exceptions\AuthenticationException;
use App\Exceptions\ValidationException;

class AuthServiceTest extends TestCase
{
    private AuthService $authService;
    private JwtService $jwtService;
    /** @var UserRepositoryInterface&MockObject */
    private UserRepositoryInterface $userRepo;
    /** @var SessionRepositoryInterface&MockObject */
    private SessionRepositoryInterface $sessionRepo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jwtService  = new JwtService([
            'secret'      => str_repeat('x', 32),
            'access_ttl'  => 900,
            'refresh_ttl' => 86400,
            'issuer'      => 'healingyuk-test',
            'audience'    => 'healingyuk-client',
        ]);

        $this->userRepo    = $this->createMock(UserRepositoryInterface::class);
        $this->sessionRepo = $this->createMock(SessionRepositoryInterface::class);

        $this->authService = new AuthService(
            $this->userRepo,
            $this->sessionRepo,
            $this->jwtService,
        );
    }

    // ----------------------------------------------------------------
    // register()
    // ----------------------------------------------------------------

    public function testRegisterThrowsWhenEmailTaken(): void
    {
        $this->userRepo
            ->expects($this->once())
            ->method('findByEmail')
            ->willReturn($this->makeUser());

        $this->expectException(ValidationException::class);

        $this->authService->register([
            'name'                  => 'Test User',
            'email'                 => 'test@example.com',
            'password'              => 'Password@1',
            'password_confirmation' => 'Password@1',
        ]);
    }

    public function testRegisterCreatesUserWhenEmailFree(): void
    {
        $this->userRepo
            ->expects($this->once())
            ->method('findByEmail')
            ->willReturn(null);

        $this->userRepo
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->makeUser());

        $result = $this->authService->register([
            'name'                  => 'New User',
            'email'                 => 'new@example.com',
            'password'              => 'Password@1',
            'password_confirmation' => 'Password@1',
        ]);

        $this->assertArrayHasKey('user', $result);
    }

    // ----------------------------------------------------------------
    // login()
    // ----------------------------------------------------------------

    public function testLoginThrowsOnUnknownEmail(): void
    {
        $this->userRepo
            ->expects($this->once())
            ->method('findByEmail')
            ->willReturn(null);

        $this->expectException(AuthenticationException::class);

        $this->authService->login(['email' => 'nobody@x.com', 'password' => 'Pass@1'], '127.0.0.1', '');
    }

    public function testLoginThrowsOnWrongPassword(): void
    {
        $user = $this->makeUser(['password' => password_hash('RealPass@1', PASSWORD_ARGON2ID)]);

        $this->userRepo
            ->expects($this->once())
            ->method('findByEmail')
            ->willReturn($user);

        $this->expectException(AuthenticationException::class);

        $this->authService->login(['email' => 'u@x.com', 'password' => 'WrongPass'], '127.0.0.1', '');
    }

    public function testLoginSucceedsWithCorrectCredentials(): void
    {
        $plainPassword = 'Correct@Pass1';
        $user = $this->makeUser(['password' => password_hash($plainPassword, PASSWORD_ARGON2ID)]);

        $this->userRepo->method('findByEmail')->willReturn($user);
        $this->userRepo->method('update')->willReturn($user);
        $this->sessionRepo->method('create')->willReturn(['id' => 1, 'token_hash' => 'hash', 'expires_at' => '2099-01-01']);

        $result = $this->authService->login(
            ['email' => 'u@x.com', 'password' => $plainPassword],
            '127.0.0.1',
            'Mozilla/5.0'
        );

        $this->assertArrayHasKey('access_token',  $result);
        $this->assertArrayHasKey('refresh_token', $result);
        $this->assertArrayHasKey('user',          $result);
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    private function makeUser(array $overrides = []): User
    {
        return User::fromArray(array_merge([
            'id'             => 1,
            'name'           => 'Test User',
            'email'          => 'test@example.com',
            'password'       => password_hash('Password@1', PASSWORD_ARGON2ID),
            'role'           => 'user',
            'phone'          => null,
            'avatar_url'     => null,
            'email_verified' => 1,
            'is_active'      => 1,
            'deleted_at'     => null,
            'created_at'     => '2025-01-01 00:00:00',
            'updated_at'     => '2025-01-01 00:00:00',
        ], $overrides));
    }
}
