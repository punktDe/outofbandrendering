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

    public function render(Node $node, string $fusionPath)
    {
        return $this->fusionRenderingService->render($node, $fusionPath);
    }

    public function renderByIdentifier(string $nodeIdentifier, string $fusionPath, string $workspace = 'live', array $contextData = [])
    {
        return $this->fusionRenderingService->renderByIdentifier($nodeIdentifier, $fusionPath, $workspace, $contextData);
    }

    public function allowsCallOfMethod($methodName): bool
    {
        return true;
    }
}
