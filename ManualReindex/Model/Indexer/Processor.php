<?php

declare(strict_types=1);

namespace CL\ManualReindex\Model\Indexer;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Indexer\Config\DependencyInfoProvider;
use Magento\Framework\Indexer\IndexerInterface;
use Magento\Framework\Indexer\StateInterface;
use Magento\Framework\Indexer\ConfigInterface;
use Magento\Indexer\Model\Processor\MakeSharedIndexValid;
use Psr\Log\LoggerInterface;
use Magento\Indexer\Model\Indexer\CollectionFactory;

/**
 * Class Processor
 * @package CL\ManualReindex\Model\Indexer
 */
class Processor
{
    /**
     * @var ConfigInterface
     */
    private ConfigInterface $config;

    /**
     * @var array
     */
    private array $sharedIndexesComplete = [];

    /**
     * @var MakeSharedIndexValid
     */
    private MakeSharedIndexValid $makeSharedIndexValid;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $indexersFactory;

    /**
     * @var DependencyInfoProvider
     */
    private DependencyInfoProvider $dependencyInfoProvider;

    /**
     * Consumer constructor.
     * @param ConfigInterface $config
     * @param MakeSharedIndexValid $makeSharedIndexValid
     * @param LoggerInterface $logger
     * @param CollectionFactory $indexersFactory
     * @param DependencyInfoProvider $dependencyInfoProvider
     */
    public function __construct(
        ConfigInterface $config,
        MakeSharedIndexValid $makeSharedIndexValid,
        LoggerInterface $logger,
        CollectionFactory $indexersFactory,
        DependencyInfoProvider $dependencyInfoProvider
    ) {
        $this->config = $config;
        $this->makeSharedIndexValid = $makeSharedIndexValid;
        $this->logger = $logger;
        $this->indexersFactory = $indexersFactory;
        $this->dependencyInfoProvider = $dependencyInfoProvider;
    }

    /**
     * Make reindex for selected indexes.
     *
     * @param array $indexerIds
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function reindexList(array $indexerIds): void
    {
        $indexers =  $this->getIndexers($indexerIds);
        foreach ($indexers as $indexer) {
            try {
                // Load indexer config and index status validation
                $indexerConfig = $this->config->getIndexer($indexer->getIndexerId());
                $this->validateIndexerStatus($indexer);

                // Shared indexers finding
                $sharedIndex = $indexerConfig['shared_index'] ?? '';
                if (!in_array($sharedIndex, $this->sharedIndexesComplete)) {
                    // Start reindexing of current index
                    $indexer->reindexAll();

                    if (!empty($sharedIndex) && $this->makeSharedIndexValid->execute($sharedIndex)) {
                        $this->sharedIndexesComplete[] = $sharedIndex;
                    }
                }
            } catch (\Throwable $e) {
                $this->logger->critical($e->getMessage());
            }
        }
    }

    /**
     * Regenerate full index
     *
     * @return void
     * @throw \Exception
     */
    public function reindexAll(): void
    {
        /** @var IndexerInterface[] $indexers */
        $indexers = $this->indexersFactory->create()->getItems();
        foreach ($indexers as $indexer) {
            try {
                $indexer->reindexAll();
            } catch (\Throwable $e) {
                $this->logger->critical($e->getMessage());
            }
        }
    }

    /**
     * Validate that indexer is not locked
     *
     * @see \Magento\Indexer\Console\Command\IndexerReindexCommand::validateIndexerStatus()
     * @param IndexerInterface $indexer
     * @return void
     * @throws LocalizedException
     */
    private function validateIndexerStatus(IndexerInterface $indexer): void
    {
        if ($indexer->getStatus() == StateInterface::STATUS_WORKING) {
            throw new LocalizedException(
                __(
                    '%1 index is locked by another reindex process. Skipping.',
                    $indexer->getTitle()
                )
            );
        }
    }

    /**
     * Return the array of all indexers with keys as indexer ids.
     *
     * @See \Magento\Indexer\Console\Command\AbstractIndexerCommand::getAllIndexers()
     * @return array|false
     */
    private function getAllIndexers(): array
    {
        $indexers = $this->indexersFactory->create()->getItems();

        return array_combine(
            array_map(
                function ($item) {
                    /** @var IndexerInterface $item */
                    return $item->getId();
                },
                $indexers
            ),
            $indexers
        );
    }

    /**
     * Return all indexer Ids which depend on the current indexer (directly or indirectly).
     *
     * @see \Magento\Indexer\Console\Command\IndexerReindexCommand::getDependentIndexerIds()
     * @param string $indexerId
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getDependentIndexerIds(string $indexerId): array
    {
        $dependentIndexerIds = [];
        foreach (array_keys($this->config->getIndexers()) as $id) {
            $dependencies = $this->dependencyInfoProvider->getIndexerIdsToRunBefore($id);
            if (array_search($indexerId, $dependencies) !== false) {
                $dependentIndexerIds[] = [$id];
                $dependentIndexerIds[] = $this->getDependentIndexerIds($id);
            }
        }

        return array_unique(array_merge([], ...$dependentIndexerIds));
    }

    /**
     * Return all indexer Ids on which the current indexer depends (directly or indirectly).
     *
     * @see \Magento\Indexer\Console\Command\IndexerReindexCommand::getRelatedIndexerIds()
     * @param string $indexerId
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getRelatedIndexerIds(string $indexerId): array
    {
        $relatedIndexerIds = [];
        foreach ($this->dependencyInfoProvider->getIndexerIdsToRunBefore($indexerId) as $relatedIndexerId) {
            $relatedIndexerIds[] = [$relatedIndexerId];
            $relatedIndexerIds[] = $this->getRelatedIndexerIds($relatedIndexerId);
        }

        return array_unique(array_merge([], ...$relatedIndexerIds));
    }

    /**
     * Returns the ordered list of indexers.
     *
     * @see \Magento\Indexer\Console\Command\IndexerReindexCommand::getIndexers()
     * @param $indexerIds
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getIndexers($indexerIds): array
    {
        $allIndexers = $this->getAllIndexers();
        $unsupportedTypes = array_diff($indexerIds, array_keys($allIndexers));
        if ($unsupportedTypes) {
            throw new \InvalidArgumentException(
                "The following requested index types are not supported: '" . join("', '", $unsupportedTypes)
                . "'." . PHP_EOL . 'Supported types: ' . join(", ", array_keys($allIndexers))
            );
        }

        $indexers = array_intersect_key($allIndexers, array_flip($indexerIds));
        if (!array_diff_key($allIndexers, $indexers)) {
            return $indexers;
        }

        $relatedIndexers = [];
        $dependentIndexers = [];
        foreach ($indexers as $indexer) {
            $relatedIndexers[] = $this->getRelatedIndexerIds($indexer->getId());
            $dependentIndexers[] = $this->getDependentIndexerIds($indexer->getId());
        }

        $relatedIndexers = array_unique(array_merge([], ...$relatedIndexers));
        $dependentIndexers = array_merge([], ...$dependentIndexers);
        $invalidRelatedIndexers = [];
        foreach ($relatedIndexers as $relatedIndexer) {
            if ($allIndexers[$relatedIndexer]->isInvalid()) {
                $invalidRelatedIndexers[] = $relatedIndexer;
            }
        }

        return array_intersect_key(
            $allIndexers,
            array_flip(
                array_unique(
                    array_merge(
                        array_keys($indexers),
                        $invalidRelatedIndexers,
                        $dependentIndexers
                    )
                )
            )
        );
    }
}
