<?php
namespace PunktDe\OutOfBandRendering\Service;

/*
 * This file is part of the PunktDe.OutOfBandRendering package.
 *
 * This package is open source software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindClosestNodeFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Package\PackageManager;
use Neos\Fusion\View\FusionView;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;

/**
 * @Flow\Scope("singleton")
 */
class FusionRenderingService
{
    #[Flow\InjectConfiguration(path: 'fusionAutoInclude')]
    protected array $packagesForFusionAutoInclude;

    public function __construct(
        private readonly PackageManager            $packageManager,
        private readonly ContentRepositoryRegistry $contentRepositoryRegistry,
        private readonly ContextBuilder            $contextBuilder,
    ) {
    }

    public function render(Node $node, string $fusionPath, array $contextData = [])
    {
        $fusionView = new FusionView();

        // TODO: Useless?
        $fusionView->setPackageKey('PunktDe.OutOfBandRendering');

        $subgraph = $this->contentRepositoryRegistry->subgraphForNode($node);
        $currentSiteNode = $subgraph->findClosestNode($node->aggregateId, FindClosestNodeFilter::create(nodeTypes: NodeTypeNameFactory::NAME_SITE));

        $fusionView->assignMultiple([
            'node' => $node,
            'documentNode' => $this->getClosestDocumentNode($node) ?: $node,
            'request' => $this->contextBuilder->buildControllerContext()->getRequest(),
            'site' => $currentSiteNode,
        ]);

        $fusionView->setFusionPathPatterns(array_map(function (string $value) {
            return $this->packageManager->getPackage($value)->getResourcesPath() . 'Private/Fusion';
        }, array_keys(array_filter($this->packagesForFusionAutoInclude))));


        $fusionView->setFusionPath($fusionPath);

        return (string)$fusionView->render();
    }

    public function renderByIdentifier(string $nodeIdentifier, string $fusionPath, string $workspaceNameString = 'live', array $contextData = [])
    {
        $nodeAggregateId = NodeAggregateId::fromString($nodeIdentifier);
        $contentRepository = $this->contentRepositoryRegistry->get(ContentRepositoryId::fromString('default'));
        $workspaceName = $workspaceNameString !== 'live' ? WorkspaceName::fromString($workspaceNameString) : WorkspaceName::forLive();
        $visibilityConstraints = VisibilityConstraints::createEmpty();
        $dimensionSpacePoint = DimensionSpacePoint::createWithoutDimensions();

        $subgraph = $contentRepository->getContentGraph($workspaceName)->getSubgraph($dimensionSpacePoint, $visibilityConstraints);
        $node = $subgraph->findNodeById($nodeAggregateId);

        if ($node !== null) {
            return $this->render($node, $fusionPath, $contextData);
        }
        return '';
    }

    private function getClosestDocumentNode(Node $node): Node
    {
        $nodeTypeManager = $this->contentRepositoryRegistry->get($node->contentRepositoryId)->getNodeTypeManager();

        while ($node !== null && !$nodeTypeManager->getNodeType($node->nodeTypeName)->isOfType('Neos.Neos:Document')) {
            $node = $node->getParent();
        }

        return $node;
    }
}
