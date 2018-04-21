<?php

declare(strict_types=1);

namespace JMS\Serializer\Marshaller\Generator\Visitor\Writer;

use Closure;
use JMS\Serializer\Marshaller\Generator\Visitor\UnmarshallingPropertyVisitorInterface;
use JMS\Serializer\Metadata\PropertyMetadata;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Closure as ClosureNode;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;

final class Direct implements UnmarshallingPropertyVisitorInterface
{
    public function visit(PropertyMetadata $metadata, Expr $reference, BuilderFactory $factory) : Node
    {
        return $factory->staticCall(
            Closure::class,
            'bind',
            [
                new ClosureNode([
                    'returnType' => 'void',
                    'uses' => [
                        new Node\Expr\ClosureUse(
                            new Variable('value')
                        )
                    ],
                    'stmts' => [
                        new Node\Expr\Assign(
                            new PropertyFetch(
                                new Variable('this'),
                                $metadata->name
                            ),
                            $reference
                        )
                    ],
                ]),
            ]
        );
    }
}
