<?php

declare(strict_types=1);

namespace CL\ManualReindex\Api\Data;

/**
 * Interface QueueMessageInterface
 * @package CL\ManualReindex\Api\Data
 */
interface QueueMessageInterface
{
    /**
     * Get indexer id
     *
     * @return string[]
     */
    public function getIndexerId(): array;

    /**
     * Set indexer id
     *
     * @param array $indexerId
     * @return QueueMessageInterface
     */
    public function setIndexerId(array $indexerId): QueueMessageInterface;
}
