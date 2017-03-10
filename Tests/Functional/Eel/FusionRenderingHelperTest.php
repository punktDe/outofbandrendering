<?php
namespace PunktDe\OutOfBandRendering\Tests\Functional\Eel;

/*
 *  (c) 2017 punkt.de GmbH - Karlsruhe, Germany - http://punkt.de
 *  All rights reserved.
 */

use Neos\ContentRepository\Tests\Functional\AbstractNodeTest;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use PunktDe\OutOfBandRendering\Eel\FusionRenderingHelper;
use PunktDe\OutOfBandRendering\Eel\FusionRenyderingHelper;

class FusionRenderingHelperTest extends AbstractNodeTest
{

    /**
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;


    public function setUp()
    {
        parent::setUp();
        $this->nodeTypeManager = $this->objectManager->get(NodeTypeManager::class);
    }

    /**
     * @test
     */
    public function render()
    {
        $testNode = $this->node->getNode('teaser/dummy42');

        $fusionRenderer = new FusionRenderingHelper();
        $result = $fusionRenderer->render($testNode);

        \Neos\Flow\var_dump($result);
    }
}