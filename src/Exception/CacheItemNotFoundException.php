<?php

namespace Moon\Cache\Exception;

use Psr\Cache\InvalidArgumentException;

class CacheItemNotFoundException extends \InvalidArgumentException implements InvalidArgumentException
{
}