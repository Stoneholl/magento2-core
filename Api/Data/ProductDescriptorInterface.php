<?php

declare(strict_types=1);

namespace Stoneholl\Core\Api\Data;

/**
 * Read-only metadata describing one locally installed Stoneholl product.
 *
 * @api
 */
interface ProductDescriptorInterface
{
    /**
     * Return the stable product code used by Stoneholl integrations.
     */
    public function getCode(): string;

    /**
     * Return the merchant-facing product name.
     */
    public function getName(): string;

    /**
     * Return Magento module names that implement the product.
     *
     * @return string[]
     */
    public function getModuleNames(): array;

    /**
     * Return Composer package names included in the product.
     *
     * @return string[]
     */
    public function getPackageNames(): array;
}
