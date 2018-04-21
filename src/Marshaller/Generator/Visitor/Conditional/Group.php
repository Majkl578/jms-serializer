<?php

declare(strict_types=1);

namespace JMS\Serializer\Marshaller\Generator\Visitor\Conditional;

use function array_flip;
use JMS\Serializer\Marshaller\Generator\Visitor\ConditionalVisitorInterface;
use JMS\Serializer\Metadata\PropertyMetadata;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\If_;
use function count;

final class Group implements ConditionalVisitorInterface
{
    public function visit(PropertyMetadata $metadata, Variable $contextReference, Node $inner, BuilderFactory $factory) : If_
    {
        if ($metadata->groups === null || count($metadata->groups) === 0) {
            return new If_(
                $factory->val(true),
                [
                    'stmts' => [$inner],
                ]
            );
        }

        return new If_(
            new NotIdentical(
                $factory->funcCall(
                    'count',
                    [
                        $factory->funcCall(
                            'array_intersect_key',
                            [
                                $factory->funcCall(
                                    'array_flip',
                                    [
                                        $factory->methodCall(
                                            $contextReference,
                                            'getGroups'
                                        )
                                    ]
                                ),
                                [
                                    $factory->val(array_flip($metadata->groups))
                                ]
                            ]
                        )
                    ]
                ),
                $factory->val(0)
            ),
            [
                'stmts' => [$inner],
            ]
        );
    }
}
