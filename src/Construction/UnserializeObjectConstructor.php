<?php

declare(strict_types=1);

namespace JMS\Serializer\Construction;

use Doctrine\Instantiator\Instantiator;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\Metadata\ClassMetadataInterface;
use JMS\Serializer\Visitor\DeserializationVisitorInterface;

final class UnserializeObjectConstructor implements ObjectConstructorInterface
{
    /** @var Instantiator */
    private $instantiator;

    public function construct(DeserializationVisitorInterface $visitor, ClassMetadataInterface $metadata, $data, array $type, DeserializationContext $context): ?object
    {
        return $this->getInstantiator()->instantiate($metadata->getName());
    }

    /**
     * @return Instantiator
     */
    private function getInstantiator()
    {
        if (null == $this->instantiator) {
            $this->instantiator = new Instantiator();
        }

        return $this->instantiator;
    }
}
