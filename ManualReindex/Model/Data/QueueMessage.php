<?php

declare(strict_types=1);

namespace CL\ManualReindex\Model\Data;

use CL\ManualReindex\Api\Data\QueueMessageInterface;

/**
 * Class QueueMessage
 * @package CL\ManualReindex\Model\Data
 */
class QueueMessage implements QueueMessageInterface
{
    /**
     * @var array
     */
    private array $indexerId;

    /**
     * Get indexer id
     *
     * @return string[]
     */
    public function getIndexerId(): array
    {
        return $this->indexerId;
    }

    /**
     * Set indexer id
     *
     * @param array $indexerId
     * @return QueueMessageInterface
     */
    public function setIndexerId(array $indexerId): QueueMessageInterface
    {
        $this->indexerId = $indexerId;
        return $this;
    }
}
