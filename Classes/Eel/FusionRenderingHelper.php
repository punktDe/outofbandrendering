<?php
namespace PunktDe\OutOfBandRendering\Eel;

/*
 *  (c) 2017 punkt.de GmbH - Karlsruhe, Germany - http://punkt.de
 *  All rights reserved.
 */

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Controller\CreateContentContextTrait;
use Neos\Flow\Http;
use Neos\Flow\Mvc;

class FusionRenderingHelper implements ProtectedContextAwareInterface
{

    use CreateContentContextTrait;

    /**
     * @Flow\Inject
     * @var \Neos\Neos\View\FusionView
     */
    protected $fusionView;

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
        $controllerContext = $this->buildControllerContext();
        $contentContext = $this->createContentContext('live', []);
        $renderableNode = $contentContext->getNodeByIdentifier($node->getIdentifier());

        $this->fusionView->setControllerContext($controllerContext);

        $this->fusionView->setFusionPath($fusionPath);
        $this->fusionView->assign('value', $renderableNode);
        return $this->fusionView->render();
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
}