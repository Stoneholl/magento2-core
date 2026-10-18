<?php

declare(strict_types=1);

namespace Stoneholl\Core\Model;

use Stoneholl\Core\Api\Data\ProductDescriptorInterface;
use Stoneholl\Core\Api\ProductProviderInterface;
use Stoneholl\Core\Api\ProductRegistryInterface;

class ProductRegistry implements ProductRegistryInterface
{
    /** @var ProductProviderInterface[] */
    private $providers;

    /** @var ProductDescriptorInterface[]|null */
    private $products;

    /**
     * @param ProductProviderInterface[] $providers
     */
    public function __construct(array $providers = [])
    {
        $this->providers = $providers;
    }

    /**
     * Aggregate and memoize only a complete valid registry, indexed by unique product code.
     *
     * @return array
     */
    public function getProducts(): array
    {
        if ($this->products !== null) {
            return $this->products;
        }

        $products = [];
        foreach ($this->providers as $provider) {
            if (!$provider instanceof ProductProviderInterface) {
                throw new \InvalidArgumentException(
                    'Stoneholl product providers must implement ProductProviderInterface.'
                );
            }
            foreach ($provider->getProducts() as $product) {
                if (!$product instanceof ProductDescriptorInterface) {
                    throw new \UnexpectedValueException('Stoneholl product providers must return product descriptors.');
                }
                $code = $product->getCode();
                if ($code === '' || isset($products[$code])) {
                    throw new \UnexpectedValueException('Stoneholl product codes must be non-empty and unique.');
                }
                $products[$code] = $product;
            }
        }

        ksort($products);
        $this->products = $products;
        return $this->products;
    }

    /**
     * Look up a registered product without creating a placeholder for unknown codes.
     *
     * @param string $code
     * @return ProductDescriptorInterface|null
     */
    public function get(string $code): ?ProductDescriptorInterface
    {
        $products = $this->getProducts();
        return $products[$code] ?? null;
    }
}
