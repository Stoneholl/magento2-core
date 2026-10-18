<?php

declare(strict_types=1);

namespace Stoneholl\Core\Model\Config\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Stoneholl\Core\Model\Config\MarketplaceUrlValidator;

class MarketplaceUrl extends Value
{
    /** @var MarketplaceUrlValidator */
    private $marketplaceUrlValidator;

    /**
     * Initialize explicit dependencies for marketplace url.
     *
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param MarketplaceUrlValidator $marketplaceUrlValidator
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        MarketplaceUrlValidator $marketplaceUrlValidator,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
        $this->marketplaceUrlValidator = $marketplaceUrlValidator;
    }

    /**
     * @inheritdoc
     */
    public function beforeSave()
    {
        $value = $this->marketplaceUrlValidator->normalize((string)$this->getValue());
        if ($value === '') {
            throw new LocalizedException(
                __('Enter an absolute HTTPS URL without credentials, a query, or a fragment.')
            );
        }

        $this->setValue($value);
        return parent::beforeSave();
    }
}
