<?php

declare(strict_types=1);

namespace JMS\Serializer\Metadata\Driver;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata as DoctrineClassMetadata;
use JMS\Serializer\Metadata\ClassMetadataInterface;
use JMS\Serializer\Metadata\ExpressionPropertyMetadataInterface;
use JMS\Serializer\Metadata\PropertyMetadataInterface;
use JMS\Serializer\Metadata\StaticPropertyMetadataInterface;
use JMS\Serializer\Metadata\VirtualPropertyMetadataInterface;
use JMS\Serializer\Type\Parser;
use JMS\Serializer\Type\ParserInterface;
use Metadata\Driver\DriverInterface;

/**
 * This class decorates any other driver. If the inner driver does not provide a
 * a property type, the decorator will guess based on Doctrine 2 metadata.
 */
abstract class AbstractDoctrineTypeDriver implements DriverInterface
{
    /**
     * Map of doctrine 2 field types to JMS\Serializer types
     * @var array
     */
    protected $fieldMapping = [
        'string' => 'string',
        'text' => 'string',
        'blob' => 'string',
        'guid' => 'string',

        'integer' => 'integer',
        'smallint' => 'integer',
        'bigint' => 'integer',

        'datetime' => 'DateTime',
        'datetimetz' => 'DateTime',
        'time' => 'DateTime',
        'date' => 'DateTime',

        'float' => 'float',
        'decimal' => 'float',

        'boolean' => 'boolean',

        'array' => 'array',
        'json_array' => 'array',
        'simple_array' => 'array<string>',
    ];
    /**
     * @var DriverInterface
     */
    protected $delegate;
    /**
     * @var ManagerRegistry
     */
    protected $registry;
    protected $typeParser;

    public function __construct(DriverInterface $delegate, ManagerRegistry $registry, ParserInterface $typeParser = null)
    {
        $this->delegate = $delegate;
        $this->registry = $registry;
        $this->typeParser = $typeParser ?: new Parser();
    }

    public function loadMetadataForClass(\ReflectionClass $class): ?\Metadata\ClassMetadata
    {
        /** @var $classMetadata ClassMetadataInterface */
        $classMetadata = $this->delegate->loadMetadataForClass($class);

        // Abort if the given class is not a mapped entity
        if (!$doctrineMetadata = $this->tryLoadingDoctrineMetadata($class->name)) {
            return $classMetadata;
        }

        $this->setDiscriminator($doctrineMetadata, $classMetadata);

        // We base our scan on the internal driver's property list so that we
        // respect any internal white/blacklisting like in the AnnotationDriver
        foreach ($classMetadata->getProperties() as $key => $propertyMetadata) {
            /** @var $propertyMetadata PropertyMetadataInterface */

            // If the inner driver provides a type, don't guess anymore.
            if ($propertyMetadata->getType() || $this->isVirtualProperty($propertyMetadata)) {
                continue;
            }

            if ($this->hideProperty($doctrineMetadata, $propertyMetadata)) {
                unset($classMetadata->getProperties()[$key]);
            }

            $this->setPropertyType($doctrineMetadata, $propertyMetadata);
        }

        return $classMetadata;
    }

    private function isVirtualProperty(PropertyMetadataInterface $propertyMetadata)
    {
        return $propertyMetadata instanceof VirtualPropertyMetadataInterface
            || $propertyMetadata instanceof StaticPropertyMetadataInterface
            || $propertyMetadata instanceof ExpressionPropertyMetadataInterface;
    }

    /**
     * @param DoctrineClassMetadata  $doctrineMetadata
     * @param ClassMetadataInterface $classMetadata
     */
    protected function setDiscriminator(DoctrineClassMetadata $doctrineMetadata, ClassMetadataInterface $classMetadata): void
    {
    }

    /**
     * @param DoctrineClassMetadata $doctrineMetadata
     * @param PropertyMetadataInterface $propertyMetadata
     */
    protected function hideProperty(DoctrineClassMetadata $doctrineMetadata, PropertyMetadataInterface $propertyMetadata): bool
    {
        return false;
    }

    /**
     * @param DoctrineClassMetadata $doctrineMetadata
     * @param PropertyMetadataInterface $propertyMetadata
     */
    protected function setPropertyType(DoctrineClassMetadata $doctrineMetadata, PropertyMetadataInterface $propertyMetadata): void
    {
    }

    /**
     * @param string $className
     *
     * @return null|DoctrineClassMetadata
     */
    protected function tryLoadingDoctrineMetadata(string $className): ?DoctrineClassMetadata
    {
        if (!$manager = $this->registry->getManagerForClass($className)) {
            return null;
        }

        if ($manager->getMetadataFactory()->isTransient($className)) {
            return null;
        }

        return $manager->getClassMetadata($className);
    }

    protected function normalizeFieldType($type): ?string
    {
        if (!isset($this->fieldMapping[$type])) {
            return null;
        }

        return $this->fieldMapping[$type];
    }
}
