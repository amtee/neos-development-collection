<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\SubgraphCachingInMemory\InMemoryCache;

use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\NodeType\NodeTypeCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;

/**
 * @internal
 */
final class AllChildNodesByNodeIdCache
{
    /**
     * @var array<string,array<string,Nodes>>
     */
    private array $childNodes = [];

    public function add(
        NodeAggregateId $parentNodeAggregateId,
        ?NodeTypeCriteria $nodeTypeCriteria,
        Nodes $childNodes
    ): void {
        $nodeTypeCriteriaKey = $nodeTypeCriteria !== null ? $nodeTypeCriteria->toFilterString() : '*';
        $this->childNodes[$parentNodeAggregateId->value][$nodeTypeCriteriaKey] = $childNodes;
    }

    public function contains(
        NodeAggregateId $parentNodeAggregateId,
        ?NodeTypeCriteria $nodeTypeCriteria
    ): bool {
        $nodeTypeCriteriaKey = $nodeTypeCriteria !== null ? $nodeTypeCriteria->toFilterString() : '*';
        return isset($this->childNodes[$parentNodeAggregateId->value][$nodeTypeCriteriaKey]);
    }

    public function findChildNodes(
        NodeAggregateId $parentNodeAggregateId,
        ?NodeTypeCriteria $nodeTypeCriteria,
    ): Nodes {
        $nodeTypeCriteriaKey = $nodeTypeCriteria !== null ? $nodeTypeCriteria->toFilterString() : '*';
        return $this->childNodes[$parentNodeAggregateId->value][$nodeTypeCriteriaKey] ?? Nodes::createEmpty();
    }

    public function countChildNodes(
        NodeAggregateId $parentNodeAggregateId,
        ?NodeTypeCriteria $nodeTypeCriteria
    ): int {
        return $this->findChildNodes($parentNodeAggregateId, $nodeTypeCriteria)->count();
    }
}
