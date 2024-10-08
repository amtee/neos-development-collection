<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Factory;

/**
 * @template T of ContentRepositoryServiceInterface
 *
 * @api
 */
interface ContentRepositoryServiceFactoryInterface
{
    /**
     * @return T
     */
    public function build(
        ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies
    ): ContentRepositoryServiceInterface;
}
