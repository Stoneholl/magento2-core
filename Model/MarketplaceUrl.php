<?php

declare(strict_types=1);

namespace Stoneholl\Core\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Stoneholl\Core\Model\Config\MarketplaceUrlValidator;

class MarketplaceUrl
{
    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /** @var MarketplaceUrlValidator */
    private $validator;

    /**
     * Resolve local navigation configuration without contacting the destination.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param MarketplaceUrlValidator $validator
     */
    public function __construct(ScopeConfigInterface $scopeConfig, MarketplaceUrlValidator $validator)
    {
        $this->scopeConfig = $scopeConfig;
        $this->validator = $validator;
    }

    /**
     * Return the Default-scope marketplace base, or no link for invalid deployment configuration.
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->validator->normalize(
            (string)$this->scopeConfig->getValue('stoneholl_core/general/marketplace_url')
        );
    }
}
