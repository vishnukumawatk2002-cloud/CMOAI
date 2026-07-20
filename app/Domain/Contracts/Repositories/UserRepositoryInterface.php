<?php

namespace App\Domain\Contracts\Repositories;

use App\Models\User;

interface UserRepositoryInterface
{
    public function findByEmail(string $email): ?User;

    public function findByGoogleId(string $googleId): ?User;

    public function create(array $data): User;
}
