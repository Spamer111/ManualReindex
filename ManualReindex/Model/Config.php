<?php

declare(strict_types=1);

namespace CL\ManualReindex\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\FlagManager;

/**
 * Class Config
 * @package CL\ManualReindex\Model
 */
class Config
{
    public const FLAG = 'full_reindex_flag';

    public const MANUAL_REINDEX = 'cl_manual_reindex/general/enabled';

    public const REINDEX_INTERVAL = 'cl_manual_reindex/general/reindex_interval';

    /**
     * @var FlagManager
     */
    private FlagManager $flagManager;

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * Config constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param FlagManager $flagManager
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        FlagManager $flagManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->flagManager = $flagManager;
    }

    /**
     * @return bool
     */
    public function isManualReindexEnable(): bool
    {
        return $this->scopeConfig->isSetFlag(self::MANUAL_REINDEX);
    }

    /**
     * @return string
     */
    public function getReindexInterval(): string
    {
        return $this->scopeConfig->getValue(self::REINDEX_INTERVAL) ?? "";
    }

    /**
     * @return array
     */
    private function getFlagData(): array
    {
        $flagData = $this->flagManager->getFlagData(self::FLAG);
        return $flagData ?? [];
    }

    /**
     * @return int
     */
    public function getLastReindexTime(): int
    {
        $flagData = $this->getFlagData();
        return isset($flagData['reindex_time']) ? $flagData['reindex_time'] : 0 ;
    }

    /**
     * @return string
     */
    public function getNameAdminWhoMadeReindex(): string
    {
        $flagData = $this->getFlagData();
        return isset($flagData['admin']) ? $flagData['admin'] : '' ;
    }
}
