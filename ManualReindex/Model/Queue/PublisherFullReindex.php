<?php

declare(strict_types=1);

namespace CL\ManualReindex\Model\Queue;

use Magento\Framework\MessageQueue\PublisherInterface;

/**
 * Class PublisherFullReindex
 * @package CL\ManualReindex\Model\Queue
 */
class PublisherFullReindex
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
     * @param string $message
     */
    public function addMessageToQueue(string $topicName, string $message)
    {
        $this->publisher->publish($topicName, $message);
    }
}
