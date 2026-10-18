<?php

declare(strict_types=1);

namespace Stoneholl\Core\Test\Unit\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\TestCase;
use Stoneholl\Core\Model\Config\MarketplaceUrlValidator;
use Stoneholl\Core\Model\MarketplaceUrl;

class MarketplaceUrlTest extends TestCase
{
    public function testNavigationBasesAreNormalizedIncludingDeploymentPaths(): void
    {
        $validator = new MarketplaceUrlValidator();
        foreach ([
            ['https://stoneholl.com', 'https://stoneholl.com'],
            ['  https://example.test/shop/  ', 'https://example.test/shop'],
            ['https://example.test/shop///', 'https://example.test/shop'],
        ] as $pair) {
            self::assertSame($pair[1], $validator->normalize($pair[0]));
        }
    }

    public function testUnsafeRuntimeConfigurationNeverProducesALink(): void
    {
        foreach ([
            '', 'http://example.test', '/shop', 'https://', 'https://example .test',
            'https://user:secret@example.test', 'https://example.test?next=bad',
            'https://example.test#bad', "https://example.test/\npath", str_repeat('a', 2049),
        ] as $url) {
            $scope = $this->createStub(ScopeConfigInterface::class);
            $scope->method('getValue')->willReturn($url);
            self::assertSame('', (new MarketplaceUrl($scope, new MarketplaceUrlValidator()))->getUrl());
        }
    }

    public function testServiceReadsOnlyDefaultScopeMarketplaceConfiguration(): void
    {
        $scope = $this->createMock(ScopeConfigInterface::class);
        $scope->expects(self::once())->method('getValue')
            ->with('stoneholl_core/general/marketplace_url')->willReturn('https://example.test/shop/');

        self::assertSame('https://example.test/shop', (new MarketplaceUrl(
            $scope,
            new MarketplaceUrlValidator()
        ))->getUrl());
    }
}
