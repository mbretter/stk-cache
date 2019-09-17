<?php

namespace Stk\Cache;

use Psr\Cache;
use Psr\SimpleCache;

class InvalidArgumentException extends \InvalidArgumentException
    implements Cache\InvalidArgumentException, SimpleCache\InvalidArgumentException
{

}