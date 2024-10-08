<?php

declare(strict_types=1);

namespace Neos\Neos\Fusion\Cache;

enum CacheFlushingStrategy
{
    case IMMEDIATE;
    case ON_SHUTDOWN;
}
