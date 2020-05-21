<?php
namespace PunktDe\OutOfBandRendering\Service;

/*
 * This file is part of the PunktDe.OutOfBandRendering package.
 *
 * This package is open source software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Flow\I18n\Exception\InvalidLocaleIdentifierException;
use Neos\Flow\Log\ThrowableStorageInterface;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\Mvc\Exception\InvalidActionNameException;
use Neos\Flow\Mvc\Exception\InvalidArgumentNameException;
use Neos\Flow\Mvc\Exception\InvalidArgumentTypeException;
use Neos\Flow\Mvc\Exception\InvalidControllerNameException;
use Neos\Fusion\Core\Runtime;
use Neos\Neos\Controller\CreateContentContextTrait;
use Neos\Flow\I18n\Service;
use Neos\Flow\I18n\Locale;
use Neos\Neos\Domain\Exception;
use Neos\Neos\Domain\Service\FusionService;
use Psr\Log\LoggerInterface;

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
     * @Flow\Inject
     * @var ContextBuilder
     */
    protected $contextBuilder;

    /**
     * @var array
     */
    protected $options = ['enableContentCache' => true];

    /**
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @Flow\Inject
     * @var ThrowableStorageInterface
     */
    protected $throwableStorage;

    /**
     * @param NodeInterface $node
     * @param string $fusionPath
     * @param array $contextData
     * @return mixed
     * @throws InvalidActionNameException
     * @throws InvalidArgumentNameException
     * @throws InvalidArgumentTypeException
     * @throws InvalidControllerNameException
     * @throws \Neos\Fusion\Exception
     * @throws Exception
     */
    public function render(NodeInterface $node, string $fusionPath, array $contextData = [])
    {
        if (!$node instanceof NodeInterface || $node === null) {
            return '';
        }

        $dimensions = $node->getDimensions();
        $context = $this->createContentContext($node->getWorkspace()->getName(), $dimensions);
        $node = $context->getNodeByIdentifier($node->getIdentifier());

        $currentSiteNode = $context->getCurrentSiteNode();
        $fusionRuntime = $this->getFusionRuntime($currentSiteNode);

        if (array_key_exists('language', $dimensions) && $dimensions['language'] !== []) {
            try {
                $currentLocale = new Locale($dimensions['language'][0]);
                $this->i18nService->getConfiguration()->setCurrentLocale($currentLocale);
                $this->i18nService->getConfiguration()->setFallbackRule([
                    'strict' => false,
                    'order' => array_reverse($dimensions['language'])
                ]);
            } catch (InvalidLocaleIdentifierException $exception) {
                $logMessage = $this->throwableStorage->logThrowable($exception);
                $this->logger->error($logMessage, LogEnvironment::fromMethodName(__METHOD__));
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
        } catch (\Exception $exception) {
            $logMessage = $this->throwableStorage->logThrowable($exception);
            $this->logger->error($logMessage, LogEnvironment::fromMethodName(__METHOD__));
        }

        return '';
    }

    /**
     * @param string $nodeIdentifier
     * @param string $fusionPath
     * @param string $workspace
     * @param array $contextData
     * @return mixed
     * @throws InvalidActionNameException
     * @throws InvalidArgumentNameException
     * @throws InvalidArgumentTypeException
     * @throws InvalidControllerNameException
     * @throws \Neos\Fusion\Exception
     * @throws Exception
     */
    public function renderByIdentifier(string $nodeIdentifier, string $fusionPath, string $workspace = 'live', array $contextData = [])
    {
        $context = $this->createContentContext($workspace);
        $node = $context->getNodeByIdentifier($nodeIdentifier);
        if ($node !== null) {
            return $this->render($node, $fusionPath, $contextData);
        }
        return '';
    }

    /**
     * @param NodeInterface $currentSiteNode
     * @return Runtime
     * @throws InvalidActionNameException
     * @throws InvalidArgumentNameException
     * @throws InvalidArgumentTypeException
     * @throws InvalidControllerNameException
     * @throws \Neos\Fusion\Exception
     * @throws Exception
     */
    protected function getFusionRuntime(NodeInterface $currentSiteNode): Runtime
    {
        if ($this->fusionRuntime === null) {
            $this->fusionRuntime = $this->fusionService->createRuntime($currentSiteNode, $this->contextBuilder->buildControllerContext());

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
    protected function getClosestDocumentNode(NodeInterface $node): NodeInterface
    {
        while ($node !== null && !$node->getNodeType()->isOfType('Neos.Neos:Document')) {
            $node = $node->getParent();
        }

        return $node;
    }
}
