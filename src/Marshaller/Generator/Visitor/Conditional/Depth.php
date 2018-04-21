<?php

declare(strict_types=1);

namespace JMS\Serializer\Marshaller\Generator\Visitor\Conditional;

use JMS\Serializer\Marshaller\Generator\Visitor\ConditionalVisitorInterface;
use JMS\Serializer\Metadata\PropertyMetadata;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp\SmallerOrEqual;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\If_;

final class Depth implements ConditionalVisitorInterface
{
    public function visit(PropertyMetadata $metadata, Variable $contextReference, Node $inner, BuilderFactory $factory) : If_
    {
        if ($metadata->maxDepth === null) {
            return new If_(
                $factory->val(true),
                [
                    'stmts' => [$inner]
                ]
            );
        }

        assert($metadata->maxDepth >= 0);

        return new If_(
            new SmallerOrEqual(
                $factory->methodCall(
                    $contextReference,
                    'getDepth'
                ),
                $factory->val($metadata->maxDepth)
            ),
            [
                'stmts' => [$inner],
            ]
        );
    }
}
