<?php

declare(strict_types=1);

namespace JMS\Serializer\Ordering;

use JMS\Serializer\Metadata\PropertyMetadataInterface;

final class AlphabeticalPropertyOrderingStrategy implements PropertyOrderingInterface
{
    /**
     * {@inheritdoc}
     */
    public function order(array $properties) : array
    {
        uasort(
            $properties,
            function (PropertyMetadataInterface $a, PropertyMetadataInterface $b) : int {
                return strcmp($a->getName(), $b->getName());
            }
        );

        return $properties;
    }
}
