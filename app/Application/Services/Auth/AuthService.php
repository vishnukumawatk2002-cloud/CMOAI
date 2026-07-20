<?php

namespace App\Application\Services\Auth;

use App\Application\DTOs\Auth\RegisterUserDTO;
use App\Domain\Contracts\Repositories\UserRepositoryInterface;
use App\Models\EmailVerification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly EmailVerificationService $verification,
    ) {
    }

    public function register(RegisterUserDTO $dto): User
    {
        return DB::transaction(function () use ($dto) {
            $user = $this->users->create([
                'first_name' => $dto->firstName,
                'last_name' => $dto->lastName,
                'email' => $dto->email,
                'password' => Hash::make($dto->password),
            ]);

            $this->verification->sendOtp($user);

            return $user;
        });
    }

    public function login(string $email, string $password): ?User
    {
        $user = $this->users->findByEmail($email);

        if (! $user || ! Hash::check($password, $user->password)) {
            return null;
        }

        return $user;
    }

    public function findOrCreateFromGoogle(object $googleUser): User
    {
        $user = $this->users->findByGoogleId($googleUser->getId())
            ?? $this->users->findByEmail($googleUser->getEmail());

        if ($user) {
            $user->update([
                'google_id' => $googleUser->getId(),
                'avatar_url' => $googleUser->getAvatar(),
            ]);

            return $user;
        }

        [$firstName, $lastName] = $this->splitName($googleUser->getName());

        return $this->users->create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $googleUser->getEmail(),
            'google_id' => $googleUser->getId(),
            'avatar_url' => $googleUser->getAvatar(),
            'email_verified_at' => now(),
        ]);
    }

    private function splitName(?string $name): array
    {
        $parts = explode(' ', trim($name ?? 'User'), 2);

        return [$parts[0], $parts[1] ?? ''];
    }
}
