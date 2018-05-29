<?php

declare(strict_types=1);

namespace JMS\Serializer\Tests\Fixtures\ExclusionStrategy;

use JMS\Serializer\Context;
use JMS\Serializer\Exclusion\ExclusionStrategyInterface;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\ClassMetadataInterface;
use JMS\Serializer\Metadata\PropertyMetadataInterface;

class AlwaysExcludeExclusionStrategy implements ExclusionStrategyInterface
{
    public function shouldSkipClass(ClassMetadataInterface $metadata, Context $context): bool
    {
        return true;
    }

    public function shouldSkipProperty(PropertyMetadataInterface $property, Context $context): bool
    {
        return false;
    }
}
