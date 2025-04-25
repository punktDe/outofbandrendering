<?php
namespace PunktDe\OutOfBandRendering\Service;

/*
 * This file is part of the PunktDe.OutOfBandRendering package.
 *
 * This package is open source software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindClosestNodeFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
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

    /**
     * @var array
     */
    #[Flow\InjectConfiguration(path: 'fusionAutoInclude')]
    protected $packagesForFusionAutoInclude;


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

        return $fusionView->render();
    }

//
//    /**
//     * @param string $nodeIdentifier
//     * @param string $fusionPath
//     * @param string $workspace
//     * @param array $contextData
//     * @return mixed
//     * @throws InvalidActionNameException
//     * @throws InvalidArgumentNameException
//     * @throws InvalidArgumentTypeException
//     * @throws InvalidControllerNameException
//     * @throws \Neos\Fusion\Exception
//     * @throws Exception
//     */
//    public function renderByIdentifier(string $nodeIdentifier, string $fusionPath, string $workspace = 'live', array $contextData = [])
//    {
//        $context = $this->createContentContext($workspace);
//        $node = $context->getNodeByIdentifier($nodeIdentifier);
//        if ($node !== null) {
//            return $this->render($node, $fusionPath, $contextData);
//        }
//        return '';
//    }
//

    private function getClosestDocumentNode(Node $node): Node
    {
        $nodeTypeManager = $this->contentRepositoryRegistry->get($node->contentRepositoryId)->getNodeTypeManager();

        while ($node !== null && !$nodeTypeManager->getNodeType($node->nodeTypeName)->isOfType('Neos.Neos:Document')) {
            $node = $node->getParent();
        }

        return $node;
    }
}
