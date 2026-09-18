<?php

declare(strict_types=1);

namespace App\Domain\Shared;

use DateTimeImmutable;

interface DomainEvent
{
    public function occurredOn(): DateTimeImmutable;
}
