<?php

declare(strict_types=1);

namespace Neos\ContentRepository\NodeAccess\FlowQueryOperations;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSucceedingSiblingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations\AbstractOperation;
use Neos\Flow\Annotations as Flow;

/**
 * "nextAll" operation working on ContentRepository nodes. It iterates over all
 * context elements and returns each following sibling or only those matching
 * the filter expression specified as optional argument.
 */
class NextAllOperation extends AbstractOperation
{
    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected static $shortName = 'nextAll';

    /**
     * {@inheritdoc}
     *
     * @var integer
     */
    protected static $priority = 0;

    /**
     * @Flow\Inject
     * @var ContentRepositoryRegistry
     */
    protected $contentRepositoryRegistry;

    /**
     * {@inheritdoc}
     *
     * @param array<int,mixed> $context (or array-like object) onto which this operation should be applied
     * @return boolean true if the operation can be applied onto the $context, false otherwise
     */
    public function canEvaluate($context)
    {
        return count($context) === 0 || (isset($context[0]) && ($context[0] instanceof Node));
    }

    /**
     * {@inheritdoc}
     *
     * @param FlowQuery<int,mixed> $flowQuery the FlowQuery object
     * @param array<int,mixed> $arguments the arguments for this operation
     * @return void
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments)
    {
        $output = [];
        $outputNodePaths = [];
        foreach ($flowQuery->getContext() as $contextNode) {
            /** @var Node $contextNode */
            $nextNodes = $this->contentRepositoryRegistry->subgraphForNode($contextNode)
                ->findSucceedingSiblingNodes(
                    $contextNode->aggregateId,
                    FindSucceedingSiblingNodesFilter::create()
                );
            foreach ($nextNodes as $nextNode) {
                if ($nextNode !== null && !isset($outputNodePaths[$nextNode->aggregateId->value])) {
                    $outputNodePaths[$nextNode->aggregateId->value] = true;
                    $output[] = $nextNode;
                }
            }
        }
        $flowQuery->setContext($output);

        if (isset($arguments[0]) && !empty($arguments[0])) {
            $flowQuery->pushOperation('filter', $arguments);
        }
    }
}
