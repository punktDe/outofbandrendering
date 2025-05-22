<?php
declare(strict_types=1);

namespace PunktDe\OutOfBandRendering\Service;

/*
 *  (c) 2018-2021 punkt.de GmbH - Karlsruhe, Germany - http://punkt.de
 *  All rights reserved.
 */

use GuzzleHttp\Psr7\Uri;
use Neos\Flow\Http\BaseUriProvider;
use Neos\Flow\Http\ServerRequestAttributes;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Mvc;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Flow\Persistence\Doctrine\Exception\DatabaseException;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\FrontendRouting\SiteDetection\SiteDetectionResult;
use Neos\Utility\ObjectAccess;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriFactoryInterface;

/**
 * @Flow\Scope("singleton")
 */
class ContextBuilder
{
    #[Flow\InjectConfiguration(path:'urlSchemaAndHost')]
    protected string $urlSchemeAndHostFromConfiguration;

    #[Flow\Inject]
    protected UriFactoryInterface $uriFactory;

    #[Flow\Inject]
    protected ServerRequestFactoryInterface $requestFactory;

    #[Flow\Inject]
    protected DomainRepository $domainRepository;

    #[Flow\Inject]
    protected SiteRepository $siteRepository;

    protected ?ControllerContext $controllerContext = null;

    #[Flow\Inject]
    protected BaseUriProvider $baseUriProvider;

    protected ?ServerRequestInterface $httpRequest = null;

    public function initializeObject(): void
    {
        // we want to have nice URLs
        putenv('FLOW_REWRITEURLS=1');
    }

    public static function buildControllerContextFromActionRequest(ActionRequest $actionRequest): ControllerContext
    {
        return new ControllerContext(
            $actionRequest,
            new Mvc\ActionResponse(),
            new Mvc\Controller\Arguments([]),
            self::getUriBuilderFromActionRequest($actionRequest)
        );
    }

    /**
     * @param string $urlSchemaAndHost Can be set if known (eg from the primary domain retrieved from the domain repository)
     * @return ControllerContext
     */
    public function buildControllerContext(string $urlSchemaAndHost = ''): ControllerContext
    {
        if ($this->controllerContext instanceof ControllerContext) {
            return $this->controllerContext;
        }

        $this->setBaseUriInBaseUriProviderIfNotSet($urlSchemaAndHost);

        if ($urlSchemaAndHost === '') {
            $urlSchemaAndHost = $this->urlSchemeAndHostFromConfiguration;
        }
        $requestUri = $this->uriFactory->createUri($urlSchemaAndHost);

        $httpRequest = $this->httpRequest ?? $this->requestFactory->createServerRequest('get', $requestUri);
        $parameters = $httpRequest->getAttribute(ServerRequestAttributes::ROUTING_PARAMETERS) ?? RouteParameters::createEmpty();

        $httpRequest = $httpRequest
            ->withAttribute(ServerRequestAttributes::ROUTING_PARAMETERS, $parameters->withParameter('requestUriHost', $requestUri->getHost()));

        $requestUriHost = (new Uri($urlSchemaAndHost))->getHost();

        try {
            if (!empty($requestUriHost)) {
                // try to get site by domain
                $activeDomain = $this->domainRepository->findOneByHost($requestUriHost, true);
                $site = $activeDomain?->getSite();
            }
            if ($site === null) {
                // try to get any site
                $site = $this->siteRepository->findFirstOnline();
            }
        } catch (DatabaseException) {
            // doctrine might have not been migrated yet or no database is connected.
        }

        // doctrine is running and we could fetch a site. This makes no promise if the content repository is set up.
        $siteDetectionResult = SiteDetectionResult::create($site->getNodeName(), $site->getConfiguration()->contentRepositoryId);
        $httpRequest = $siteDetectionResult->storeInRequest($httpRequest);

        $actionRequest = ActionRequest::fromHttpRequest($httpRequest);
        $actionRequest->setFormat('html');

        $this->controllerContext = self::buildControllerContextFromActionRequest($actionRequest);
        return $this->controllerContext;
    }

    public function setHttpRequest(ServerRequestInterface $httpRequest): self
    {
        $this->httpRequest = $httpRequest;
        return $this;
    }

    protected function setBaseUriInBaseUriProviderIfNotSet(string $urlSchemaAndHost = ''): void
    {
        $urlSchemaAndHost = $urlSchemaAndHost === '' ? '/' : $urlSchemaAndHost;

        try {
            $this->baseUriProvider->getConfiguredBaseUriOrFallbackToCurrentRequest();
        } catch (\Exception $exception) {
            ObjectAccess::setProperty($this->baseUriProvider, 'configuredBaseUri', $urlSchemaAndHost, true);
        }
    }

    private static function getUriBuilderFromActionRequest(ActionRequest $actionRequest): UriBuilder
    {
        $uriBuilder = new UriBuilder();
        $uriBuilder->setRequest($actionRequest);
        return $uriBuilder;
    }
}
