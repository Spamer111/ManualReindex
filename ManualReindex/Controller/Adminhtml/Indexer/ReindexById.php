<?php

declare(strict_types=1);

namespace CL\ManualReindex\Controller\Adminhtml\Indexer;

use CL\ManualReindex\Api\Data\QueueMessageInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Message\ManagerInterface;
use CL\ManualReindex\Model\Queue\PublisherReindexList;

/**
 * Class ReindexById
 * @package CL\ManualReindex\Controller\Adminhtml\Indexer
 */
class ReindexById implements HttpPostActionInterface
{
    public const INDEXATION_BY_ID_TOPIC_NAME = 'partial.indexation.by.id.list.queue';

    public const ADMIN_RESOURCE = 'CL_ManualReindex::indexer_reindex';

    /**
     * @var PublisherReindexList
     */
    protected PublisherReindexList $publisher;

    /**
     * @var QueueMessageInterface
     */
    protected QueueMessageInterface $queueMessage;

    /**
     * @var RequestInterface
     */
    protected RequestInterface $request;

    /**
     * @var AuthorizationInterface
     */
    protected AuthorizationInterface $authorization;

    /**
     * @var ManagerInterface
     */
    protected ManagerInterface $messageManager;

    /**
     * @var ResultFactory
     */
    protected ResultFactory $resultFactory;

    /**
     * ReindexById constructor.
     * @param PublisherReindexList $publisher
     * @param QueueMessageInterface $queueMessage
     * @param AuthorizationInterface $authorization
     * @param ResultFactory $resultFactory
     * @param ManagerInterface $messageManager
     * @param RequestInterface $request
     */
    public function __construct(
        PublisherReindexList $publisher,
        QueueMessageInterface $queueMessage,
        AuthorizationInterface $authorization,
        ResultFactory $resultFactory,
        ManagerInterface $messageManager,
        RequestInterface $request
    ) {
        $this->publisher = $publisher;
        $this->queueMessage = $queueMessage;
        $this->request = $request;
        $this->authorization = $authorization;
        $this->resultFactory = $resultFactory;
        $this->messageManager = $messageManager;
    }

    /**
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        if (!$this->isAllowed()) {
            $this->messageManager->addErrorMessage(__('You don\'t have permission to perform this operation.'));
            return $resultRedirect->setPath('*/*/list');
        }

        $indexerIds = $this->request->getParam('indexer_ids');
        if ($indexerIds) {
            $indexerIds = explode(',', $indexerIds);
            $this->queueMessage->setIndexerId($indexerIds);
            $this->publisher->addMessageToQueue(self::INDEXATION_BY_ID_TOPIC_NAME, $this->queueMessage);
            $this->messageManager->addSuccessMessage(__('Indexing started.'));
        } else {
            $this->messageManager->addErrorMessage(__('Please select indexers.'));
        }
        return $resultRedirect->setPath('*/*/list');
    }

    /**
     * Check admin permissions for this controller
     *
     * @return boolean
     */
    protected function isAllowed()
    {
        return $this->authorization->isAllowed(self::ADMIN_RESOURCE);
    }
}
