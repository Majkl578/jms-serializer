<?php

declare(strict_types=1);

namespace JMS\Serializer\Naming;

use JMS\Serializer\Metadata\PropertyMetadataInterface;

final class IdenticalPropertyNamingStrategy implements PropertyNamingStrategyInterface
{
    public function translateName(PropertyMetadataInterface $property): string
    {
        return $property->getName();
    }
}
