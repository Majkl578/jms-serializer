<?php

declare(strict_types=1);

namespace JMS\Serializer\Construction;

use JMS\Serializer\DeserializationContext;
use JMS\Serializer\Metadata\ClassMetadataInterface;
use JMS\Serializer\Visitor\DeserializationVisitorInterface;

/**
 * Implementations of this interface construct new objects during deserialization.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
interface ObjectConstructorInterface
{
    /**
     * Constructs a new object.
     *
     * Implementations could for example create a new object calling "new", use
     * "unserialize" techniques, reflection, or other means.
     *
     * @param DeserializationVisitorInterface $visitor
     * @param ClassMetadataInterface          $metadata
     * @param mixed                           $data
     * @param array                           $type ["name" => string, "params" => array]
     * @param DeserializationContext          $context
     *
     * @return object
     */
    public function construct(DeserializationVisitorInterface $visitor, ClassMetadataInterface $metadata, $data, array $type, DeserializationContext $context): ?object;
}
