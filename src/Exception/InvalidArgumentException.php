<?php

declare(strict_types=1);

namespace Moon\Cache\Exception;

class InvalidArgumentException extends \InvalidArgumentException implements \Psr\Cache\InvalidArgumentException
{
}