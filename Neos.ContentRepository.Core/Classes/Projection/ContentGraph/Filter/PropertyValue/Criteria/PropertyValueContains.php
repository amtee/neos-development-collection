<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria;

use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;

/**
 * Criteria that matches if a property contains the specified string
 *     "prop1 *= 'foo'"
 *
 * @see PropertyValueCriteriaParser
 * @api
 */
final class PropertyValueContains implements PropertyValueCriteriaInterface
{
    private function __construct(
        public readonly PropertyName $propertyName,
        public readonly string $value,
    ) {
    }

    public static function create(PropertyName $propertyName, string $value): self
    {
        return new self($propertyName, $value);
    }
}
