<?php

declare(strict_types=1);

namespace JMS\Serializer\Marshaller;

use JMS\Serializer\DeserializationContext;

interface MarshallerInterface
{
    public function __invoke(object $object, DeserializationContext $context) : string;
}
