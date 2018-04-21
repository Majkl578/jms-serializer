<?php

declare(strict_types=1);

namespace JMS\Serializer\Marshaller\Generator\Visitor;

use JMS\Serializer\Metadata\PropertyMetadata;
use PhpParser\Node;

final class MarshallingPropertyVisitor implements MarshallingPropertyVisitorInterface
{
    public function visit(PropertyMetadata $metadata) : Node
    {

    }
}
