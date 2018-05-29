<?php

declare(strict_types=1);

namespace JMS\Serializer\Ordering;

use JMS\Serializer\Metadata\PropertyMetadataInterface;

interface PropertyOrderingInterface
{
    /**
     * @param PropertyMetadataInterface[] $properties name => property
     * @return PropertyMetadataInterface[] name => property
     */
    public function order(array $properties) : array;
}
