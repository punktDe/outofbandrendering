<?php
namespace PunktDe\OutOfBandRendering\Service;

/*
 * This file is part of the PunktDe.OutOfBandRendering package.
 *
 * This package is open source software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\I18n\Exception\InvalidLocaleIdentifierException;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Fusion\Core\Runtime;
use Neos\Neos\Controller\CreateContentContextTrait;
use Neos\Flow\Http;
use Neos\Flow\Mvc;
use Neos\Flow\I18n\Service;
use Neos\Flow\I18n\Locale;
use Neos\Neos\Domain\Service\FusionService;

class FusionRenderingService
{
    use CreateContentContextTrait;

    /**
     * @Flow\Inject
     * @var Service
     */
    protected $i18nService;

    /**
     * @var Runtime
     */
    protected $fusionRuntime;

    /**
     * @Flow\Inject
     * @var FusionService
     */
    protected $fusionService;

    /**
     * @Flow\InjectConfiguration(path="urlSchemaAndHost")
     * @var string
     */
    protected $urlSchemeAndHost;

    /**
     * @var array
     */
    protected $options = ['enableContentCache' => true];

    public function initializeObject()
    {
        // we want to have nice URLs
        putenv('FLOW_REWRITEURLS=1');
    }

    /**
     * @param NodeInterface $node
     * @param string $fusionPath
     * @param array $contextData
     * @return string
     * @throws \Neos\Fusion\Exception
     * @throws \Neos\Neos\Domain\Exception
     */
    public function render(NodeInterface $node, string $fusionPath, array $contextData = []): string
    {
        if (!$node instanceof NodeInterface || $node === null) {
            return '';
        }

        $dimensions = $node->getDimensions();
        $context = $this->createContentContext($node->getWorkspace()->getName(), $dimensions);
        $node = $context->getNodeByIdentifier($node->getIdentifier());

        $currentSiteNode = $context->getCurrentSiteNode();
        $fusionRuntime = $this->getFusionRuntime($currentSiteNode);

        if (array_key_exists('language', $dimensions) && $dimensions['language'] !== []) {
            try {
                $currentLocale = new Locale($dimensions['language'][0]);
                $this->i18nService->getConfiguration()->setCurrentLocale($currentLocale);
                $this->i18nService->getConfiguration()->setFallbackRule([
                    'strict' => false,
                    'order' => array_reverse($dimensions['language'])
                ]);
            } catch (InvalidLocaleIdentifierException $e) {
                // TODO: implement logging
            }
        }

        $fusionRuntime->pushContextArray(array_merge([
            'node' => $node,
            'documentNode' => $this->getClosestDocumentNode($node) ?: $node,
            'site' => $currentSiteNode,
            'editPreviewMode' => null,
        ], $contextData));

        try {
            $output = $fusionRuntime->render($fusionPath);
            $fusionRuntime->popContext();
            return $output;
        } catch (\Exception $e) {
            // TODO: implement logging
        }

        return '';
    }

    /**
     * @param string $nodeIdentifier
     * @param string $fusionPath
     * @param string $workspace
     * @param array $contextData
     * @return string
     * @throws \Neos\Fusion\Exception
     * @throws \Neos\Neos\Domain\Exception
     */
    public function renderByIdentifier(string $nodeIdentifier, string $fusionPath, string $workspace = 'live', array $contextData = []): string
    {
        $context = $this->createContentContext($workspace);
        $node = $context->getNodeByIdentifier($nodeIdentifier);
        if ($node !== null) {
            return $this->render($node, $fusionPath, $contextData);
        }
        return '';
    }

    /**
     * @return ControllerContext
     */
    protected function buildControllerContext(): ControllerContext
    {
        $httpRequest = Http\Request::create(new Http\Uri($this->urlSchemeAndHost));
        $httpRequest->setBaseUri(new Http\Uri($this->urlSchemeAndHost));

        return new ControllerContext(
            new Mvc\ActionRequest($httpRequest),
            new Http\Response(),
            new Mvc\Controller\Arguments(),
            new Mvc\Routing\UriBuilder()
        );
    }

    /**
     * @param NodeInterface $currentSiteNode
     * @return Runtime
     * @throws \Neos\Fusion\Exception
     * @throws \Neos\Neos\Domain\Exception
     */
    protected function getFusionRuntime(NodeInterface $currentSiteNode): Runtime
    {
        if ($this->fusionRuntime === null) {
            $this->fusionRuntime = $this->fusionService->createRuntime($currentSiteNode, $this->buildControllerContext());

            if (isset($this->options['enableContentCache']) && $this->options['enableContentCache'] !== null) {
                $this->fusionRuntime->setEnableContentCache($this->options['enableContentCache']);
            }
        }

        return $this->fusionRuntime;
    }

    /**
     * @param NodeInterface $node
     * @return NodeInterface
     */
    protected function getClosestDocumentNode(NodeInterface $node): NodeInterface
    {
        while ($node !== null && !$node->getNodeType()->isOfType('Neos.Neos:Document')) {
            $node = $node->getParent();
        }

        return $node;
    }
}
