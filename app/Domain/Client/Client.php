<?php

declare(strict_types=1);

namespace App\Domain\Client;

use InvalidArgumentException;

final class Client
{
    public function __construct(
        private ?int $id,
        private string $firstName,
        private string $lastName,
        private string $dni,
        private string $phone,
        private ?string $email,
    ) {
        $this->firstName = self::normalizeName($firstName, 'firstName');
        $this->lastName = self::normalizeName($lastName, 'lastName');
        $this->dni = self::normalizeDni($dni);
        $this->phone = self::normalizePhone($phone);
        $this->email = $email !== null && trim($email) !== ''
            ? self::normalizeEmail($email)
            : null;

        if ($this->id !== null && $this->id <= 0) {
            throw new InvalidArgumentException('Client id must be greater than zero.');
        }
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function firstName(): string
    {
        return $this->firstName;
    }

    public function lastName(): string
    {
        return $this->lastName;
    }

    public function dni(): string
    {
        return $this->dni;
    }

    public function phone(): string
    {
        return $this->phone;
    }

    public function email(): ?string
    {
        return $this->email;
    }

    public function assignId(int $id): void
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Client id must be greater than zero.');
        }

        $this->id = $id;
    }

    public function updatePhone(string $phone): void
    {
        $this->phone = self::normalizePhone($phone);
    }

    public function updateEmail(string $email): void
    {
        $this->email = self::normalizeEmail($email);
    }

    private static function normalizeName(string $value, string $field): string
    {
        $value = trim($value);

        if ($value === '') {
            throw new InvalidArgumentException(sprintf('%s cannot be empty.', $field));
        }

        return $value;
    }

    private static function normalizeDni(string $dni): string
    {
        $dni = preg_replace('/\s+/', '', trim($dni)) ?? '';

        if (!preg_match('/^\d{7,10}$/', $dni)) {
            throw new InvalidArgumentException('DNI must contain 7 to 10 digits.');
        }

        return $dni;
    }

    private static function normalizePhone(string $phone): string
    {
        $phone = trim($phone);

        if (!preg_match('/^\+?[0-9\-\s]{6,20}$/', $phone)) {
            throw new InvalidArgumentException('Phone must be a valid number with 6 to 20 characters.');
        }

        return $phone;
    }

    private static function normalizeEmail(string $email): string
    {
        $email = trim($email);

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Email must be a valid email address.');
        }

        return $email;
    }
}
