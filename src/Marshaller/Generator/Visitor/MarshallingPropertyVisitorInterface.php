<?php

declare(strict_types=1);

namespace JMS\Serializer\Marshaller\Generator\Visitor;

use JMS\Serializer\Metadata\PropertyMetadata;
use PhpParser\BuilderFactory;
use PhpParser\Node;

interface MarshallingPropertyVisitorInterface
{
    public function visit(PropertyMetadata $metadata, Node $reference, BuilderFactory $factory) : Node;
}
