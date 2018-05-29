<?php

declare(strict_types=1);

namespace JMS\Serializer\Exclusion;

use JMS\Serializer\Context;
use JMS\Serializer\Metadata\ClassMetadataInterface;
use JMS\Serializer\Metadata\PropertyMetadataInterface;

final class VersionExclusionStrategy implements ExclusionStrategyInterface
{
    private $version;

    public function __construct(string $version)
    {
        $this->version = $version;
    }

    /**
     * {@inheritDoc}
     */
    public function shouldSkipClass(ClassMetadataInterface $metadata, Context $navigatorContext): bool
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function shouldSkipProperty(PropertyMetadataInterface $property, Context $navigatorContext): bool
    {
        if ((null !== $version = $property->getSinceVersion()) && version_compare($this->version, $version, '<')) {
            return true;
        }

        if ((null !== $version = $property->getUntilVersion()) && version_compare($this->version, $version, '>')) {
            return true;
        }

        return false;
    }
}
