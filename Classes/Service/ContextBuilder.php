<?php
declare(strict_types=1);

namespace PunktDe\OutOfBandRendering\Service;

/*
 *  (c) 2018 punkt.de GmbH - Karlsruhe, Germany - http://punkt.de
 *  All rights reserved.
 */

use Neos\Flow\Http\ServerRequestAttributes;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Mvc;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

/**
 * @Flow\Scope("singleton")
 */
class ContextBuilder
{
    /**
     * @Flow\InjectConfiguration(path="urlSchemaAndHost")
     * @var string
     */
    protected $urlSchemeAndHostFromConfiguration;

    /**
     * @Flow\Inject
     * @var UriFactoryInterface
     */
    protected $uriFactory;

    /**
     * @Flow\Inject
     * @var ServerRequestFactoryInterface
     */
    protected $requestFactory;

    /**
     * @Flow\Inject
     * @var Mvc\ActionRequestFactory
     */
    protected $actionRequestFactory;

    /**
     * @var ControllerContext
     */
    protected $controllerContext;

    public function initializeObject(): void
    {
        // we want to have nice URLs
        putenv('FLOW_REWRITEURLS=1');
    }

    /**
     * @param string $urlSchemaAndHost Can be set if known (eg from the primary domain retrieved from the domain repository)
     * @return ControllerContext
     */
    public function buildControllerContext(string $urlSchemaAndHost = ''): ControllerContext
    {
        if ($urlSchemaAndHost === '') {
            $urlSchemaAndHost = $this->urlSchemeAndHostFromConfiguration;
        }
        $requestUri = $this->uriFactory->createUri($urlSchemaAndHost);

        $httpRequest = $this->requestFactory->createServerRequest('get', $requestUri);
        $parameters = $httpRequest->getAttribute(ServerRequestAttributes::ROUTING_PARAMETERS) ?? RouteParameters::createEmpty();
        $httpRequest = $httpRequest->withAttribute(ServerRequestAttributes::ROUTING_PARAMETERS, $parameters->withParameter('requestUriHost', $requestUri->getHost()));

        $request = ActionRequest::fromHttpRequest($httpRequest);
        $request->setFormat('html');

        $uriBuilder = new UriBuilder();
        $uriBuilder->setRequest($request);

        if (!($this->controllerContext instanceof ControllerContext)) {
            $this->controllerContext = new ControllerContext(
                $request,
                new Mvc\ActionResponse(),
                new Mvc\Controller\Arguments([]),
                $uriBuilder
            );
        }

        return $this->controllerContext;
    }
}
