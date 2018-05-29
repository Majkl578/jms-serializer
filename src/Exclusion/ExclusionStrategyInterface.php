<?php

declare(strict_types=1);

namespace JMS\Serializer\Exclusion;

use JMS\Serializer\Context;
use JMS\Serializer\Metadata\ClassMetadataInterface;
use JMS\Serializer\Metadata\PropertyMetadataInterface;

/**
 * Interface for exclusion strategies.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
interface ExclusionStrategyInterface
{
    /**
     * Whether the class should be skipped.
     *
     * @param ClassMetadataInterface $metadata
     *
     * @return boolean
     */
    public function shouldSkipClass(ClassMetadataInterface $metadata, Context $context): bool;

    /**
     * Whether the property should be skipped.
     *
     * @param PropertyMetadataInterface $property
     *
     * @return boolean
     */
    public function shouldSkipProperty(PropertyMetadataInterface $property, Context $context): bool;
}
