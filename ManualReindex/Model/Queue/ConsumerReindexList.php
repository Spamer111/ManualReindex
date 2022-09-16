<?php

declare(strict_types=1);

namespace CL\ManualReindex\Model\Queue;

use CL\ManualReindex\Api\Data\QueueMessageInterface;
use CL\ManualReindex\Model\Indexer\Processor;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Config\ScopeInterface;
use Psr\Log\LoggerInterface;

/**
 * Class ConsumerReindexList
 * @package CL\ManualReindex\Model\Queue
 */
class ConsumerReindexList
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
     * Process message from queue
     *
     * @param QueueMessageInterface $message
     */
    public function process(QueueMessageInterface $message): void
    {
        try {
            if ($indexerIds = $message->getIndexerId()) {
                // Changing area to state like in native magento reindex
                $this->configScope->setCurrentScope(Area::AREA_ADMINHTML);
                $this->state->emulateAreaCode(
                    Area::AREA_ADMINHTML,
                    [$this->indexerProcessor, 'reindexList'],
                    [$indexerIds]
                );
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }
    }
}
