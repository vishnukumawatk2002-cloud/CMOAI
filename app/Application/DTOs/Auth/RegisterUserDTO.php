<?php

namespace App\Application\DTOs\Auth;

readonly class RegisterUserDTO
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $password,
    ) {}
}
