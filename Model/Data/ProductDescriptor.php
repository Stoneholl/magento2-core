<?php

declare(strict_types=1);

namespace Stoneholl\Core\Model\Data;

use Stoneholl\Core\Api\Data\ProductDescriptorInterface;

class ProductDescriptor implements ProductDescriptorInterface
{
    /** @var string */
    private $code;

    /** @var string */
    private $name;

    /** @var string[] */
    private $moduleNames;

    /** @var string[] */
    private $packageNames;

    /**
     * @param string $code
     * @param string $name
     * @param string[] $moduleNames
     * @param string[] $packageNames
     */
    public function __construct(string $code, string $name, array $moduleNames, array $packageNames)
    {
        $this->code = $code;
        $this->name = $name;
        $this->moduleNames = array_values($moduleNames);
        $this->packageNames = array_values($packageNames);
    }

    /**
     * @inheritdoc
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @inheritdoc
     */
    public function getModuleNames(): array
    {
        return $this->moduleNames;
    }

    /**
     * @inheritdoc
     */
    public function getPackageNames(): array
    {
        return $this->packageNames;
    }
}
