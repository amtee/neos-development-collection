<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Fusion;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\ActionResponse;
use Neos\Flow\Mvc\Dispatcher;
use Neos\Flow\Mvc\Exception\InvalidActionNameException;
use Neos\Flow\Mvc\Exception\InvalidControllerNameException;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Fusion\FusionObjects\AbstractArrayFusionObject;

/**
 * A Fusion Plugin object.
 */
class PluginImplementation extends AbstractArrayFusionObject
{
    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @Flow\Inject
     * @var Dispatcher
     */
    protected $dispatcher;

    protected ?Node $node;

    protected ?Node $documentNode;

    /**
     * @return string
     */
    public function getPackage()
    {
        return $this->fusionValue('package');
    }

    /**
     * @return string
     */
    public function getSubpackage()
    {
        return $this->fusionValue('subpackage');
    }

    /**
     * @return string
     */
    public function getController()
    {
        return $this->fusionValue('controller');
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->fusionValue('action');
    }

    /**
     * @return string|null
     */
    public function getArgumentNamespace()
    {
        return $this->fusionValue('argumentNamespace');
    }

    /**
     * Build the pluginRequest object
     */
    protected function buildPluginRequest(): ActionRequest
    {
        $parentRequest = $this->runtime->fusionGlobals->get('request');
        if (!$parentRequest instanceof ActionRequest) {
            throw new \RuntimeException('Fusion Plugins must be rendered with an ActionRequest set as fusion-global.', 1706624581);
        }
        $pluginRequest = $parentRequest->createSubRequest();
        $pluginRequest->setArgumentNamespace('--' . $this->getPluginNamespace());
        $this->passArgumentsToPluginRequest($pluginRequest);

        $pluginRequest = $this->resolveDispatchArgumentsForPluginRequest($pluginRequest, $this->node);

        foreach ($this->properties as $key => $value) {
            $pluginRequest->setArgument('__' . $key, $this->fusionValue($key));
        }

        return $pluginRequest;
    }

    /**
     * @param ActionRequest $pluginRequest
     * @param Node|null $node
     * @return ActionRequest
     * @throws InvalidActionNameException
     * @throws InvalidControllerNameException
     */
    protected function resolveDispatchArgumentsForPluginRequest(
        ActionRequest $pluginRequest,
        Node $node = null
    ): ActionRequest {
        $packageKey = $this->getPackage();
        $subpackageKey = $this->getSubpackage();
        $controller = $this->getController();
        $action = $this->getAction() ?: 'index';

        if ($node !== null) {
            $packageKey = $node->getProperty('package') ?: $packageKey;
            $subpackageKey = $node->getProperty('subpackage') ?: $subpackageKey;
            $controller = $node->getProperty('controller') ?: $controller;
            $action = $node->getProperty('action') ?: $action;
        }

        if (empty($pluginRequest->getControllerPackageKey())) {
            $pluginRequest->setControllerPackageKey($packageKey);
        }

        if (empty($pluginRequest->getControllerSubpackageKey())) {
            $pluginRequest->setControllerSubpackageKey($subpackageKey);
        }

        if (empty($pluginRequest->getControllerName())) {
            $pluginRequest->setControllerName($controller);
        }

        if (empty($pluginRequest->getControllerActionName())) {
            $pluginRequest->setControllerActionName($action);
        }

        return $pluginRequest;
    }

    /**
     * Returns the rendered content of this plugin
     *
     * @return string The rendered content as a string
     * @throws InvalidActionNameException
     * @throws InvalidControllerNameException
     * @throws \Neos\Flow\Configuration\Exception\NoSuchOptionException
     * @throws \Neos\Flow\Mvc\Controller\Exception\InvalidControllerException
     * @throws \Neos\Flow\Mvc\Exception\InfiniteLoopException
     * @throws \Neos\Flow\Mvc\Exception\InvalidArgumentNameException
     * @throws \Neos\Flow\Mvc\Exception\InvalidArgumentTypeException
     * @throws \Neos\Flow\Security\Exception\AccessDeniedException
     * @throws \Neos\Flow\Security\Exception\AuthenticationRequiredException
     * @throws \Neos\Flow\Security\Exception\MissingConfigurationException
     */
    public function evaluate(): string
    {
        $currentContext = $this->runtime->getCurrentContext();
        $this->node = $currentContext['node'];
        $this->documentNode = $currentContext['documentNode'];
        $parentResponse = $this->runtime->getControllerContext()->getResponse();
        $pluginResponse = $this->dispatcher->dispatch($this->buildPluginRequest());

        // We need to make sure to not merge content up into the parent ActionResponse
        // because that would break the Fusion HttpResponse.
        $content = $pluginResponse->getBody()->getContents();

        // hacky, but part of the deal. We have to manipulate the global response to redirect for example.
        // transfer possible headers that have been set dynamically
        foreach ($pluginResponse->getHeaders() as $name => $values) {
            $parentResponse->setHttpHeader($name, $values);
        }
        // if the status code is 200 we assume it's the default and will not overrule it
        if ($pluginResponse->getStatusCode() !== 200) {
            $parentResponse->setStatusCode($pluginResponse->getStatusCode());
        }

        return $content;
    }

    /**
     * Returns the plugin namespace that will be prefixed to plugin parameters in URIs.
     * By default this is <plugin_class_name>
     *
     * @return string
     */
    protected function getPluginNamespace(): string
    {
        if ($this->getArgumentNamespace() !== null) {
            return $this->getArgumentNamespace();
        }

        if ($this->node instanceof Node) {
            $nodeArgumentNamespace = $this->node->getProperty('argumentNamespace');
            if ($nodeArgumentNamespace !== null) {
                return $nodeArgumentNamespace;
            }

            $nodeArgumentNamespace = $this->node->nodeTypeName->value;
            $nodeArgumentNamespace = str_replace(':', '-', $nodeArgumentNamespace);
            $nodeArgumentNamespace = str_replace('.', '_', $nodeArgumentNamespace);
            $nodeArgumentNamespace = strtolower($nodeArgumentNamespace);
            return $nodeArgumentNamespace;
        }

        $argumentNamespace = str_replace(
            [':', '.', '\\'],
            ['_', '_', '_'],
            ($this->getPackage() . '_' . $this->getSubpackage() . '-' . $this->getController())
        );
        $argumentNamespace = strtolower($argumentNamespace);

        return $argumentNamespace;
    }

    /**
     * Pass the arguments which were addressed to the plugin to its own request
     *
     * @param ActionRequest $pluginRequest The plugin request
     * @return void
     */
    protected function passArgumentsToPluginRequest(ActionRequest $pluginRequest)
    {
        $arguments = $pluginRequest->getMainRequest()->getPluginArguments();
        $pluginNamespace = $this->getPluginNamespace();
        if (isset($arguments[$pluginNamespace])) {
            $pluginRequest->setArguments($arguments[$pluginNamespace]);
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->evaluate();
    }
}
