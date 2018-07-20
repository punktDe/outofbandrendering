<?php
namespace PunktDe\OutOfBandRendering\Eel;

/*
 * This file is part of the PunktDe.OutOfBandRendering package.
 *
 * This package is open source software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

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
     * @throws \Neos\Fusion\Exception
     * @throws \Neos\Neos\Domain\Exception
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
     * @throws \Neos\Fusion\Exception
     * @throws \Neos\Neos\Domain\Exception
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
