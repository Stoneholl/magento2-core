<?php

declare(strict_types=1);

namespace Stoneholl\Core\Model\Config;

class MarketplaceUrlValidator
{
    /**
     * Normalize an HTTPS navigation base while rejecting ambiguous or unsafe URL components.
     *
     * @param string $value
     * @return string
     */
    public function normalize(string $value): string
    {
        $value = trim($value);
        if ($value === '' || strlen($value) > 2048
            || preg_match('/[\x00-\x20\x7f]/', $value) === 1
            || filter_var($value, FILTER_VALIDATE_URL) === false
        ) {
            return '';
        }

        // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged -- Inspect every URL component.
        $parts = parse_url($value);
        if (!is_array($parts)
            || strtolower((string)($parts['scheme'] ?? '')) !== 'https'
            || empty($parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['query'])
            || isset($parts['fragment'])
        ) {
            return '';
        }

        return rtrim($value, '/');
    }
}
