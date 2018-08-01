<?php
namespace PunktDe\OutOfBandRendering\Service;

/*
 *  (c) 2018 punkt.de GmbH - Karlsruhe, Germany - http://punkt.de
 *  All rights reserved.
 */

use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Http;
use Neos\Flow\Mvc;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class ContextBuilder
{
    /**
     * @Flow\InjectConfiguration(path="urlSchemaAndHost")
     * @var string
     */
    protected $urlSchemeAndHost;

    /**
     * @var ControllerContext
     */
    protected $controllerContext;

    public function initializeObject()
    {
        // we want to have nice URLs
        putenv('FLOW_REWRITEURLS=1');
    }

    /**
     * @return ControllerContext
     */
    public function buildControllerContext(): ControllerContext
    {
        if(!($this->controllerContext instanceof ControllerContext)) {
            $httpRequest = Http\Request::create(new Http\Uri($this->urlSchemeAndHost));
            $httpRequest->setBaseUri(new Http\Uri($this->urlSchemeAndHost));

            $this->controllerContext = new ControllerContext(
                new Mvc\ActionRequest($httpRequest),
                new Http\Response(),
                new Mvc\Controller\Arguments(),
                new Mvc\Routing\UriBuilder()
            );
        }

        return $this->controllerContext;
    }
}
