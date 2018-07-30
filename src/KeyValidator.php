<?php

declare(strict_types=1);

namespace Moon\Cache;

use Moon\Cache\Exception\InvalidArgumentException;

trait KeyValidator
{
    /**
     * Check that the key is a valid PSR-6 key.
     *
     * @throws InvalidArgumentException
     */
    protected function validateKey(string $key): void
    {
        $keyLength = \mb_strlen($key);
        // String length must be at least 1 and less then 64
        if ($keyLength <= 0 || 64 < $keyLength) {
            throw new InvalidArgumentException("The key '$key' must be length from 1 up to 64 characters");
        }

        // If string contains invalid character throws the exception
        if (!\preg_match('#^[A-Za-z0-9._]+$#', $key)) {
            throw new InvalidArgumentException("The key '$key' contains invalid characters. Supported characters are A-Z a-z 0-9 _ and .");
        }
    }
}
