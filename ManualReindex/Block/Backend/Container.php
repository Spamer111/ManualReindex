<?php

declare(strict_types=1);

namespace CL\ManualReindex\Block\Backend;

use Magento\Backend\Block\Widget\Grid\Container as GridContainer;
use Magento\Backend\Block\Widget\Context;
use CL\ManualReindex\Model\Config;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Class Container
 * @package CL\ManualReindex\Block\Backend
 */
class Container extends GridContainer
{
    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var TimezoneInterface
     */
    private TimezoneInterface $timezoneInterface;

    /**
     * @var string
     */
    private $timeLeftUntilReindex;

    /**
     * Container constructor.
     * @param Context $context
     * @param TimezoneInterface $timezoneInterface
     * @param Config $config
     * @param array $data
     */
    public function __construct(
        Context $context,
        TimezoneInterface $timezoneInterface,
        Config $config,
        array $data = []
    ) {
        $this->config = $config;
        $this->timezoneInterface = $timezoneInterface;
        parent::__construct($context, $data);
    }

    /**
     * Initialize object state with incoming parameters
     */
    protected function _construct()
    {
        $this->config->getLastReindexTime();
        if ($this->config->isManualReindexEnable()) {
            $this->_controller = 'indexer';
            $this->_blockGroup = 'Magento_Indexer';
            $this->_headerText = __('Indexer Management');
            if ($this->isAllowedReindex()) {
                $this->buttonList->add(
                    'full_reindex',
                    [
                        'label' => __('Full Reindex'),
                        'onclick' => 'setLocation(\'' . $this->getFullReindexUrl() . '\')',
                        'class' => 'add primary'
                    ]
                );
            } else {
                $this->buttonList->add(
                    'full_reindex',
                    [
                        'label' => __(
                            'Full Reindex (' .
                            $this->config->getNameAdminWhoMadeReindex() . ' | ' .
                            $this->timeLeftUntilReindex . ')'
                        ),
                        'onclick' => 'setLocation(\'' . $this->getFullReindexUrl() . '\')',
                        'class' => 'add primary',
                        'disabled' => true
                    ]
                );
            }
        }
        parent::_construct();
        $this->buttonList->remove('add');
    }

    /**
     * @return string
     */
    private function getFullReindexUrl(): string
    {
        return $this->getUrl('*/indexer/fullReindex');
    }

    /**
     * @return bool
     */
    private function isAllowedReindex(): bool
    {
        $result = true;
        $lastReindexTime = $this->config->getLastReindexTime();
        $reindexInterval = $this->config->getReindexInterval();
        if ($lastReindexTime && $reindexInterval) {
            $currentTime = $this->timezoneInterface->date();
            $lastReindexTimeModify = $this->timezoneInterface->date($lastReindexTime)->modify(
                '+' . $reindexInterval . ' hours'
            );
            if ($currentTime < $lastReindexTimeModify) {
                $result = false;
                $this->timeLeftUntilReindex =
                    $this->timezoneInterface->date()->diff($lastReindexTimeModify)->format('%h:%i');
            }
        }
        return $result;
    }
}
