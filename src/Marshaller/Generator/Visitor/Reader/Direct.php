<?php

declare(strict_types=1);

namespace JMS\Serializer\Marshaller\Generator\Visitor\Reader;

use Closure;
use JMS\Serializer\Marshaller\Generator\Visitor\MarshallingPropertyVisitorInterface;
use JMS\Serializer\Metadata\PropertyMetadata;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\Node\Expr\Closure as ClosureNode;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Return_;

final class Direct implements MarshallingPropertyVisitorInterface
{
    public function visit(PropertyMetadata $metadata, Node $reference, BuilderFactory $factory) : Node
    {
        return new Expression(
            $factory->staticCall(
                '\\' . Closure::class,
                'bind',
                [
                    new ClosureNode([
                        'stmts' => [
                            new Return_(
                                new PropertyFetch(
                                    new Variable('this'),
                                    $metadata->name
                                )
                            )
                        ],
                    ]),
                    $reference
                ]
            )
        );
    }
}
