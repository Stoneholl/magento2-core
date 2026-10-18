<?php

declare(strict_types=1);

namespace Stoneholl\Core\Block\Adminhtml\Product;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Stoneholl\Core\Api\ProductRegistryInterface;
use Stoneholl\Core\Model\MarketplaceUrl;

class ListBlock extends Template
{
    /** @var ProductRegistryInterface */
    private $productRegistry;

    /** @var MarketplaceUrl */
    private $marketplaceUrl;

    /**
     * Initialize explicit dependencies for list block.
     *
     * @param Context $context
     * @param ProductRegistryInterface $productRegistry
     * @param MarketplaceUrl $marketplaceUrl
     * @param array $data
     */
    public function __construct(
        Context $context,
        ProductRegistryInterface $productRegistry,
        MarketplaceUrl $marketplaceUrl,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->productRegistry = $productRegistry;
        $this->marketplaceUrl = $marketplaceUrl;
    }

    /**
     * Return a validated navigation-only destination for the Products page.
     *
     * @return string
     */
    public function getMarketplaceUrl(): string
    {
        return $this->marketplaceUrl->getUrl();
    }

    /**
     * Build the suggestion-page link without transmitting module or installation data.
     *
     * @return string
     */
    public function getFeedbackUrl(): string
    {
        $base = $this->marketplaceUrl->getUrl();
        return $base === '' ? '' : $base . '/feedback';
    }

    /**
     * Expose the offline registry to the escaped Admin product template.
     *
     * @return array
     */
    public function getProducts(): array
    {
        return $this->productRegistry->getProducts();
    }
}
