<?php

declare(strict_types=1);

namespace JMS\Serializer\Marshaller\Generator\Visitor;

use JMS\Serializer\Metadata\PropertyMetadata;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\Node\Expr;

interface UnmarshallingPropertyVisitorInterface
{
    public function visit(PropertyMetadata $metadata, Expr $reference, BuilderFactory $factory) : Node;
}
