<?php
namespace PunktDe\OutOfBandRendering\Eel;

/*
 *  (c) 2017 punkt.de GmbH - Karlsruhe, Germany - http://punkt.de
 *  All rights reserved.
 */

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Core\Runtime;
use Neos\Neos\Controller\CreateContentContextTrait;
use Neos\Flow\Http;
use Neos\Flow\Mvc;
use Neos\Flow\I18n\Service;
use Neos\Flow\I18n\Locale;
use Neos\Neos\Domain\Service\FusionService;

class FusionRenderingHelper implements ProtectedContextAwareInterface
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
     * @return string
     */
    public function render(NodeInterface $node, $fusionPath)
    {
        $contentContext = $this->createContentContext('live', $node->getDimensions());
        $currentNode = $contentContext->getNodeByIdentifier($node->getIdentifier());

        $currentSiteNode = $currentNode->getContext()->getCurrentSiteNode();
        $fusionRuntime = $this->getFusionRuntime($currentSiteNode);

        $dimensions = $currentNode->getContext()->getDimensions();
        if (array_key_exists('language', $dimensions) && $dimensions['language'] !== []) {
            $currentLocale = new Locale($dimensions['language'][0]);
            $this->i18nService->getConfiguration()->setCurrentLocale($currentLocale);
            $this->i18nService->getConfiguration()->setFallbackRule(['strict' => false, 'order' => array_reverse($dimensions['language'])]);
        }

        $fusionRuntime->pushContextArray([
            'node' => $currentNode,
            'documentNode' => $this->getClosestDocumentNode($currentNode) ?: $currentNode,
            'site' => $currentSiteNode,
            'editPreviewMode' => null
        ]);
        $output = $fusionRuntime->render($fusionPath);
        $fusionRuntime->popContext();
        return $output;
    }


    /**
     * @param string $methodName
     * @return bool
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }

    /**
     * @return Mvc\Controller\ControllerContext
     */
    protected function buildControllerContext()
    {
        $httpRequest = Http\Request::create(new Http\Uri('https://punkt.de'));

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