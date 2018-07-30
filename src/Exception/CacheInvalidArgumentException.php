<?php

declare(strict_types=1);

namespace Moon\Cache\Exception;

use Psr\Cache\InvalidArgumentException;

class CacheInvalidArgumentException extends \InvalidArgumentException implements InvalidArgumentException
{
}
