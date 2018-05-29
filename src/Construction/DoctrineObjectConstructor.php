<?php

declare(strict_types=1);

namespace JMS\Serializer\Construction;

use Doctrine\Common\Persistence\ManagerRegistry;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\Exception\InvalidArgumentException;
use JMS\Serializer\Exception\ObjectConstructionException;
use JMS\Serializer\Metadata\ClassMetadataInterface;
use JMS\Serializer\Visitor\DeserializationVisitorInterface;

/**
 * Doctrine object constructor for new (or existing) objects during deserialization.
 */
final class DoctrineObjectConstructor implements ObjectConstructorInterface
{
    const ON_MISSING_NULL = 'null';
    const ON_MISSING_EXCEPTION = 'exception';
    const ON_MISSING_FALLBACK = 'fallback';
    /**
     * @var string
     */
    private $fallbackStrategy;

    private $managerRegistry;
    private $fallbackConstructor;

    /**
     * Constructor.
     *
     * @param ManagerRegistry $managerRegistry Manager registry
     * @param ObjectConstructorInterface $fallbackConstructor Fallback object constructor
     * @param string $fallbackStrategy
     */
    public function __construct(ManagerRegistry $managerRegistry, ObjectConstructorInterface $fallbackConstructor, $fallbackStrategy = self::ON_MISSING_NULL)
    {
        $this->managerRegistry = $managerRegistry;
        $this->fallbackConstructor = $fallbackConstructor;
        $this->fallbackStrategy = $fallbackStrategy;
    }

    /**
     * {@inheritdoc}
     */
    public function construct(DeserializationVisitorInterface $visitor, ClassMetadataInterface $metadata, $data, array $type, DeserializationContext $context): ?object
    {
        // Locate possible ObjectManager
        $objectManager = $this->managerRegistry->getManagerForClass($metadata->getName());

        if (!$objectManager) {
            // No ObjectManager found, proceed with normal deserialization
            return $this->fallbackConstructor->construct($visitor, $metadata, $data, $type, $context);
        }

        // Locate possible ClassMetadata
        $classMetadataFactory = $objectManager->getMetadataFactory();

        if ($classMetadataFactory->isTransient($metadata->getName())) {
            // No ClassMetadata found, proceed with normal deserialization
            return $this->fallbackConstructor->construct($visitor, $metadata, $data, $type, $context);
        }

        // Managed entity, check for proxy load
        if (!\is_array($data)) {
            // Single identifier, load proxy
            return $objectManager->getReference($metadata->getName(), $data);
        }

        // Fallback to default constructor if missing identifier(s)
        $classMetadata = $objectManager->getClassMetadata($metadata->getName());
        $identifierList = [];

        foreach ($classMetadata->getIdentifierFieldNames() as $name) {

            if (isset($metadata->getProperties()[$name])) {
                $dataName = $metadata->getProperties()[$name]->getSerializedName();
            } else {
                $dataName = $name;
            }

            if (!array_key_exists($dataName, $data)) {
                return $this->fallbackConstructor->construct($visitor, $metadata, $data, $type, $context);
            }
            $identifierList[$name] = $data[$dataName];
        }

        // Entity update, load it from database
        $object = $objectManager->find($metadata->getName(), $identifierList);

        if (null === $object) {
            switch ($this->fallbackStrategy) {
                case self::ON_MISSING_NULL:
                    return null;
                case self::ON_MISSING_EXCEPTION:
                    throw new ObjectConstructionException(sprintf("Entity %s can not be found", $metadata->getName()));
                case self::ON_MISSING_FALLBACK:
                    return $this->fallbackConstructor->construct($visitor, $metadata, $data, $type, $context);
                default:
                    throw new InvalidArgumentException("The provided fallback strategy for the object constructor is not valid");
            }
        }

        $objectManager->initializeObject($object);

        return $object;
    }
}
