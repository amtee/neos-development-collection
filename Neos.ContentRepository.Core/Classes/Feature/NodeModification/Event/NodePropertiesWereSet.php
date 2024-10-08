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

namespace Neos\ContentRepository\Core\Feature\NodeModification\Event;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Feature\Common\EmbedsContentStreamId;
use Neos\ContentRepository\Core\Feature\Common\EmbedsNodeAggregateId;
use Neos\ContentRepository\Core\Feature\Common\EmbedsWorkspaceName;
use Neos\ContentRepository\Core\Feature\Common\PublishableToWorkspaceInterface;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyNames;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * When a node property is changed, this event is triggered.
 *
 * The projectors need to MERGE all the SerializedPropertyValues in these events (per node)
 * to get an up to date view of all the properties of a node.
 *
 * NOTE: if a value is set to NULL in SerializedPropertyValues, this means the key should be unset,
 * because we treat NULL and "not set" the same from an API perspective.
 *
 * @api events are the persistence-API of the content repository
 */
final readonly class NodePropertiesWereSet implements
    EventInterface,
    PublishableToWorkspaceInterface,
    EmbedsContentStreamId,
    EmbedsNodeAggregateId,
    EmbedsWorkspaceName
{
    public function __construct(
        public WorkspaceName $workspaceName,
        public ContentStreamId $contentStreamId,
        public NodeAggregateId $nodeAggregateId,
        public OriginDimensionSpacePoint $originDimensionSpacePoint,
        /** the covered dimension space points for this modification - i.e. where this change is visible */
        public DimensionSpacePointSet $affectedDimensionSpacePoints,
        public SerializedPropertyValues $propertyValues,
        public PropertyNames $propertiesToUnset
    ) {
    }

    public function getContentStreamId(): ContentStreamId
    {
        return $this->contentStreamId;
    }

    public function getNodeAggregateId(): NodeAggregateId
    {
        return $this->nodeAggregateId;
    }

    public function getWorkspaceName(): WorkspaceName
    {
        return $this->workspaceName;
    }

    public function getOriginDimensionSpacePoint(): OriginDimensionSpacePoint
    {
        return $this->originDimensionSpacePoint;
    }

    public function withWorkspaceNameAndContentStreamId(WorkspaceName $targetWorkspaceName, ContentStreamId $contentStreamId): self
    {
        return new self(
            $targetWorkspaceName,
            $contentStreamId,
            $this->nodeAggregateId,
            $this->originDimensionSpacePoint,
            $this->affectedDimensionSpacePoints,
            $this->propertyValues,
            $this->propertiesToUnset
        );
    }

    public function mergeProperties(self $other): self
    {
        return new self(
            $this->workspaceName,
            $this->contentStreamId,
            $this->nodeAggregateId,
            $this->originDimensionSpacePoint,
            $this->affectedDimensionSpacePoints,
            $this->propertyValues->merge($other->propertyValues),
            $this->propertiesToUnset->merge($other->propertiesToUnset)
        );
    }

    public static function fromArray(array $values): EventInterface
    {
        return new self(
            WorkspaceName::fromString($values['workspaceName']),
            ContentStreamId::fromString($values['contentStreamId']),
            NodeAggregateId::fromString($values['nodeAggregateId']),
            OriginDimensionSpacePoint::fromArray($values['originDimensionSpacePoint']),
            DimensionSpacePointSet::fromArray($values['affectedDimensionSpacePoints']),
            SerializedPropertyValues::fromArray($values['propertyValues']),
            PropertyNames::fromArray($values['propertiesToUnset'])
        );
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
