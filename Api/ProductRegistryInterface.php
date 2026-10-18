<?php

declare(strict_types=1);

namespace Stoneholl\Core\Api;

use Stoneholl\Core\Api\Data\ProductDescriptorInterface;

/**
 * Read-only aggregate of products registered by installed modules.
 *
 * @api
 */
interface ProductRegistryInterface
{
    /**
     * Return descriptors indexed by stable product code.
     *
     * @return ProductDescriptorInterface[]
     */
    public function getProducts(): array;

    /**
     * Return a descriptor by stable product code when registered.
     *
     * @param string $code
     */
    public function get(string $code): ?ProductDescriptorInterface;
}
