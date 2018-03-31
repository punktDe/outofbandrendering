<?php
namespace PunktDe\OutOfBandRendering\Service;

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\I18n\Exception\InvalidLocaleIdentifierException;
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
     * @var array
     */
    protected $options = ['enableContentCache' => true];

    public function initializeObject() {
        // we want to have nice URLs
        putenv('FLOW_REWRITEURLS=1');
    }

    /**
     * @param NodeInterface $node
     * @param string $fusionPath
     * @param array $contextData
     * @return string
     */
    public function render(NodeInterface $node, $fusionPath, array $contextData = [])
    {
        if (!$node instanceof NodeInterface || $node === null) {
            return '';
        }

        $currentSiteNode = $node->getContext()->getCurrentSiteNode();
        $fusionRuntime = $this->getFusionRuntime($currentSiteNode);

        $dimensions = $node->getContext()->getDimensions();
        if (array_key_exists('language', $dimensions) && $dimensions['language'] !== []) {
            try {
                $currentLocale = new Locale($dimensions['language'][0]);
                $this->i18nService->getConfiguration()->setCurrentLocale($currentLocale);
                $this->i18nService->getConfiguration()->setFallbackRule(['strict' => false, 'order' => array_reverse($dimensions['language'])]);
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
     */
    public function renderByIdentifier(string $nodeIdentifier, string $fusionPath, string $workspace = 'live', array $contextData = []): string {
        $context = $this->createContentContext($workspace);
        $node = $context->getNodeByIdentifier($nodeIdentifier);
        if ($node !== null) {
            return $this->render($node, $fusionPath, $contextData);
        }
        return '';
    }

    /**
     * @return Mvc\Controller\ControllerContext
     */
    protected function buildControllerContext()
    {
        $schemeAndHost = 'https://punkt.de/';

        $httpRequest = Http\Request::create(new Http\Uri($schemeAndHost));
        $httpRequest->setBaseUri(new Http\Uri($schemeAndHost));

        return new Mvc\Controller\ControllerContext(
            new Mvc\ActionRequest($httpRequest),
            new Http\Response(),
            new Mvc\Controller\Arguments(),
            new Mvc\Routing\UriBuilder()
        );
    }

    /**
     * @param NodeInterface $currentSiteNode
     * @return Runtime
     */
    protected function getFusionRuntime(NodeInterface $currentSiteNode)
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
    protected function getClosestDocumentNode(NodeInterface $node)
    {
        while ($node !== null && !$node->getNodeType()->isOfType('Neos.Neos:Document')) {
            $node = $node->getParent();
        }
        return $node;
    }
}
