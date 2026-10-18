<?php

declare(strict_types=1);

namespace Stoneholl\Core\Api;

use Stoneholl\Core\Api\Data\ProductDescriptorInterface;

/**
 * Extension point through which a module registers local products.
 *
 * Implementations must not perform network requests.
 *
 * @api
 */
interface ProductProviderInterface
{
    /**
     * Return locally registered product descriptors.
     *
     * @return ProductDescriptorInterface[]
     */
    public function getProducts(): array;
}
