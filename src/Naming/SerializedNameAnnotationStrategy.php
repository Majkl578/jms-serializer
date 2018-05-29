<?php

declare(strict_types=1);

namespace JMS\Serializer\Naming;

use JMS\Serializer\Metadata\PropertyMetadataInterface;

/**
 * Naming strategy which uses an annotation to translate the property name.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
final class SerializedNameAnnotationStrategy implements PropertyNamingStrategyInterface
{
    private $delegate;

    public function __construct(PropertyNamingStrategyInterface $namingStrategy)
    {
        $this->delegate = $namingStrategy;
    }

    /**
     * {@inheritDoc}
     */
    public function translateName(PropertyMetadataInterface $property): string
    {
        if (null !== $name = $property->getSerializedName()) {
            return $name;
        }

        return $this->delegate->translateName($property);
    }
}
