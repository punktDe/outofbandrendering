<?php
namespace PunktDe\OutOfBandRendering\Eel;

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;
use PunktDe\OutOfBandRendering\Service\FusionRenderingService;

class FusionRenderingHelper implements ProtectedContextAwareInterface
{
    /**
     * @Flow\Inject
     * @var FusionRenderingService
     */
    protected $fusionRenderingService;

    /**
     * @param NodeInterface $node
     * @param string $fusionPath
     * @return string
     */
    public function render(NodeInterface $node, $fusionPath)
    {
        return $this->fusionRenderingService->render($node, $fusionPath);
    }

    /**
     * @param string $nodeIdentifier
     * @param string $fusionPath
     * @param string $workspace
     * @param array $contextData
     * @return string
     */
    public function renderByIdentifier($nodeIdentifier, $fusionPath, $workspace = 'live', array $contextData = [])
    {
        return $this->fusionRenderingService->renderByIdentifier($nodeIdentifier, $fusionPath);
    }

    /**
     * @param string $methodName
     * @return bool
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }

}
