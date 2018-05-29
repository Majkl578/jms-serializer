<?php

declare(strict_types=1);

namespace JMS\Serializer\Naming;

use JMS\Serializer\Metadata\PropertyMetadataInterface;

/**
 * Generic naming strategy which translates a camel-cased property name.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
final class CamelCaseNamingStrategy implements PropertyNamingStrategyInterface
{
    private $separator;
    private $lowerCase;

    public function __construct(string $separator = '_', bool $lowerCase = true)
    {
        $this->separator = $separator;
        $this->lowerCase = $lowerCase;
    }

    /**
     * {@inheritDoc}
     */
    public function translateName(PropertyMetadataInterface $property): string
    {
        $name = preg_replace('/[A-Z]+/', $this->separator . '\\0', $property->getName());

        if ($this->lowerCase) {
            return strtolower($name);
        }

        return ucfirst($name);
    }
}
