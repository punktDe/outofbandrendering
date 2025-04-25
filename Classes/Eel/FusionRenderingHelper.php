<?php
namespace PunktDe\OutOfBandRendering\Eel;

/*
 * This file is part of the PunktDe.OutOfBandRendering package.
 *
 * This package is open source software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;
use PunktDe\OutOfBandRendering\Service\FusionRenderingService;

class FusionRenderingHelper implements ProtectedContextAwareInterface
{
    #[Flow\Inject]
    protected FusionRenderingService $fusionRenderingService;

    /**
     * @return mixed
     * @throws \Neos\Flow\Mvc\Exception\InvalidActionNameException
     * @throws \Neos\Flow\Mvc\Exception\InvalidArgumentNameException
     * @throws \Neos\Flow\Mvc\Exception\InvalidArgumentTypeException
     * @throws \Neos\Flow\Mvc\Exception\InvalidControllerNameException
     * @throws \Neos\Fusion\Exception
     * @throws \Neos\Neos\Domain\Exception
     */
    public function render(Node $node, string $fusionPath)
    {
        return $this->fusionRenderingService->render($node, $fusionPath);
    }
//
//    /**
//     * @param string $nodeIdentifier
//     * @param string $fusionPath
//     * @param string $workspace
//     * @param array $contextData
//     * @return mixed
//     * @throws \Neos\Flow\Mvc\Exception\InvalidActionNameException
//     * @throws \Neos\Flow\Mvc\Exception\InvalidArgumentNameException
//     * @throws \Neos\Flow\Mvc\Exception\InvalidArgumentTypeException
//     * @throws \Neos\Flow\Mvc\Exception\InvalidControllerNameException
//     * @throws \Neos\Fusion\Exception
//     * @throws \Neos\Neos\Domain\Exception
//     */
//    public function renderByIdentifier($nodeIdentifier, $fusionPath, $workspace = 'live', array $contextData = [])
//    {
//        return $this->fusionRenderingService->renderByIdentifier($nodeIdentifier, $fusionPath, $workspace, $contextData);
//    }

    public function allowsCallOfMethod($methodName): bool
    {
        return true;
    }

}
