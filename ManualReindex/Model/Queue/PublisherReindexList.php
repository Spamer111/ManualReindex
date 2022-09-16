<?php

declare(strict_types=1);

namespace CL\ManualReindex\Model\Queue;

use Magento\Framework\MessageQueue\PublisherInterface;
use CL\ManualReindex\Api\Data\QueueMessageInterface;

/**
 * Class PublisherReindexList
 * @package CL\ManualReindex\Model\Queue
 */
class PublisherReindexList
{
    /**
     * @var PublisherInterface
     */
    private PublisherInterface $publisher;

    /**
     * WhiteRabbitPublisher constructor
     *
     * @param PublisherInterface $publisher
     */
    public function __construct(
        PublisherInterface $publisher
    ) {
        $this->publisher = $publisher;
    }

    /**
     * Add message to queue
     *
     * @param string $topicName
     * @param QueueMessageInterface $message
     */
    public function addMessageToQueue($topicName, QueueMessageInterface $message)
    {
        $this->publisher->publish($topicName, $message);
    }
}
