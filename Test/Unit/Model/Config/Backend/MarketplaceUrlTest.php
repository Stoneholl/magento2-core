<?php

declare(strict_types=1);

namespace Stoneholl\Core\Test\Unit\Model\Config\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use PHPUnit\Framework\TestCase;
use Stoneholl\Core\Model\Config\MarketplaceUrlValidator;
use Stoneholl\Core\Model\Config\Backend\MarketplaceUrl;

class MarketplaceUrlTest extends TestCase
{
    public function testValidValueIsNormalizedBeforeSave(): void
    {
        $backend = $this->createBackend();
        $backend->setValue('  https://stoneholl.com/  ');

        $backend->beforeSave();

        self::assertSame('https://stoneholl.com', $backend->getValue());
    }

    public function testInvalidValueIsRejectedBeforeSave(): void
    {
        $backend = $this->createBackend();
        $backend->setValue('https://user:secret@stoneholl.com');

        $this->expectException(LocalizedException::class);
        $backend->beforeSave();
    }

    private function createBackend(): MarketplaceUrl
    {
        $context = $this->createStub(Context::class);
        $context->method('getEventDispatcher')->willReturn($this->createStub(ManagerInterface::class));

        return new MarketplaceUrl(
            $context,
            $this->createStub(Registry::class),
            $this->createStub(ScopeConfigInterface::class),
            $this->createStub(TypeListInterface::class),
            new MarketplaceUrlValidator()
        );
    }
}
