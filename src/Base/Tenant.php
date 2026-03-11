<?php

declare(strict_types=1);

namespace Marktic\Cmp\Base;

use InvalidArgumentException;

final class Tenant
{
    public function __construct(
        public readonly string $type,
        public readonly int $id,
    ) {
        if (trim($type) === '') {
            throw new InvalidArgumentException('Tenant type cannot be empty.');
        }

        if ($id <= 0) {
            throw new InvalidArgumentException('Tenant ID must be a positive integer.');
        }
    }

    public function equals(self $other): bool
    {
        return $this->type === $other->type && $this->id === $other->id;
    }

    public function __toString(): string
    {
        return sprintf('%s/%d', $this->type, $this->id);
    }
}
