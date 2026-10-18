<?php

declare(strict_types=1);

namespace Stoneholl\Core\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Stoneholl\Core\Api\ProductProviderInterface;
use Stoneholl\Core\Model\Data\ProductDescriptor;
use Stoneholl\Core\Model\ProductRegistry;

class ProductRegistryTest extends TestCase
{
    public function testProductsAreIndexedAndSortedByStableCode(): void
    {
        $provider = new class implements ProductProviderInterface {
            public function getProducts(): array
            {
                return [
                    new ProductDescriptor('zulu', 'Zulu', ['Vendor_Zulu'], ['vendor/zulu']),
                    new ProductDescriptor('alpha', 'Alpha', ['Vendor_Alpha'], ['vendor/alpha']),
                ];
            }
        };

        $registry = new ProductRegistry([$provider]);

        self::assertSame(['alpha', 'zulu'], array_keys($registry->getProducts()));
        self::assertSame('Alpha', $registry->get('alpha')->getName());
        self::assertNull($registry->get('missing'));
    }

    public function testDuplicateProductCodeIsRejected(): void
    {
        $provider = new class implements ProductProviderInterface {
            public function getProducts(): array
            {
                return [
                    new ProductDescriptor('same', 'First', [], []),
                    new ProductDescriptor('same', 'Second', [], []),
                ];
            }
        };

        $this->expectException(\UnexpectedValueException::class);
        (new ProductRegistry([$provider]))->getProducts();
    }

    public function testInvalidProviderIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new ProductRegistry([new \stdClass()]))->getProducts();
    }
}
