<?php

declare(strict_types=1);

namespace JMS\Serializer\Marshaller\Generator\Visitor;

use JMS\Serializer\Metadata\PropertyMetadata;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\If_;

interface ConditionalVisitorInterface
{
    public function visit(PropertyMetadata $metadata, Variable $contextReference, Node $inner, BuilderFactory $factory) : If_;
}
