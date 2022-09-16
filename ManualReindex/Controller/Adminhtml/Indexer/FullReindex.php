<?php

namespace CL\ManualReindex\Controller\Adminhtml\Indexer;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\FlagManager;
use Magento\Framework\Message\ManagerInterface;
use CL\ManualReindex\Model\Queue\PublisherFullReindex;
use Magento\Backend\Model\Auth\Session;
use CL\ManualReindex\Model\Config;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Class FullReindex
 * @package CL\ManualReindex\Controller\Adminhtml\Indexer
 */
class FullReindex implements HttpGetActionInterface
{
    public const FULL_REINDEX_TOPIC_NAME = 'manual.full.reindex.queue';

    public const ADMIN_RESOURCE = 'CL_ManualReindex::indexer_reindex';

    /**
     * @var PublisherFullReindex
     */
    protected PublisherFullReindex $publisher;

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
     * @var FlagManager
     */
    private FlagManager $flagManager;

    /**
     * @var Session
     */
    private Session $authSession;

    /**
     * @var TimezoneInterface
     */
    private TimezoneInterface $timezoneInterface;

    /**
     * FullReindex constructor.
     * @param PublisherFullReindex $publisher
     * @param AuthorizationInterface $authorization
     * @param ResultFactory $resultFactory
     * @param ManagerInterface $messageManager
     * @param FlagManager $flagManager
     * @param TimezoneInterface $timezoneInterface
     * @param Session $authSession
     */
    public function __construct(
        PublisherFullReindex $publisher,
        AuthorizationInterface $authorization,
        ResultFactory $resultFactory,
        ManagerInterface $messageManager,
        FlagManager $flagManager,
        TimezoneInterface $timezoneInterface,
        Session $authSession
    ) {
        $this->publisher = $publisher;
        $this->authorization = $authorization;
        $this->resultFactory = $resultFactory;
        $this->messageManager = $messageManager;
        $this->flagManager = $flagManager;
        $this->timezoneInterface = $timezoneInterface;
        $this->authSession = $authSession;
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

        // save flag data
        $this->flagManager->saveFlag(
            Config::FLAG,
            [
            'reindex_time' => $this->timezoneInterface->date()->getTimestamp(),
            'admin' => $this->getCurrentAdminName()
            ]
        );

        $this->publisher->addMessageToQueue(self::FULL_REINDEX_TOPIC_NAME, 'full reindex');
        $this->messageManager->addSuccessMessage(__('Indexing started.'));
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

    /**
     * Get current admin name
     *
     * @return string
     */
    private function getCurrentAdminName(): string
    {
        $currentUser = $this->authSession->getUser();
        return $currentUser ? $currentUser->getName() : '';
    }
}
