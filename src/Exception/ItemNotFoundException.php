<?php

declare(strict_types=1);

namespace Moon\Cache\Exception;

use Psr\Cache\CacheException;

class ItemNotFoundException extends \Exception implements CacheException
{
}