<?php

declare(strict_types=1);

namespace CL\ManualReindex\Model\Queue;

use CL\ManualReindex\Model\Indexer\Processor;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Config\ScopeInterface;
use Psr\Log\LoggerInterface;

/**
 * Class ConsumerFullReindex
 * @package CL\ManualReindex\Model\Queue
 */
class ConsumerFullReindex
{
    /**
     * @var Processor
     */
    private Processor $indexerProcessor;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var State
     */
    private State $state;

    /**
     * @var ScopeInterface
     */
    private ScopeInterface $configScope;

    /**
     * Consumer constructor.
     * @param Processor $indexerProcessor
     * @param LoggerInterface $logger
     * @param State $state
     * @param ScopeInterface $configScope
     */
    public function __construct(
        Processor $indexerProcessor,
        LoggerInterface $logger,
        State $state,
        ScopeInterface $configScope
    ) {
        $this->logger = $logger;
        $this->indexerProcessor = $indexerProcessor;
        $this->state = $state;
        $this->configScope = $configScope;
    }

    /**
     * Queue process
     */
    public function process()
    {
        try {
            // Changing area to state like in native magento reindex
            $this->configScope->setCurrentScope(Area::AREA_ADMINHTML);
            $this->state->emulateAreaCode(
                Area::AREA_ADMINHTML,
                [$this->indexerProcessor, 'reindexAll']
            );
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }
    }
}
