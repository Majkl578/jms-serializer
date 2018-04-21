<?php

declare(strict_types=1);

namespace JMS\Serializer\Marshaller\Generator;

use JMS\Serializer\DeserializationContext;
use JMS\Serializer\Marshaller\Generator\Visitor\Conditional\Depth;
use JMS\Serializer\Marshaller\Generator\Visitor\Conditional\Group;
use JMS\Serializer\Marshaller\Generator\Visitor\Reader\Direct;
use JMS\Serializer\Marshaller\MarshallerInterface;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use function array_map;
use function array_values;
use function sha1;
use function strtr;

final class MarshallerGenerator
{
    /** @var BuilderFactory */
    private $factory;

    public function __construct()
    {
        $this->factory = new BuilderFactory();
    }

    public function __invoke(ClassMetadata $metadata) : Node
    {
        return $this->factory->namespace('JMSGenerated')
            ->addStmt(
                $this->factory->class(
                    strtr($metadata->name, '\\', '_') . '__' . sha1($metadata->serialize())
                )
                ->makeFinal()
                ->implement('\\' . MarshallerInterface::class)
                ->addStmt(
                    $this->factory->method('__construct')
                        ->makePublic()
//                        ->addParam(
//                            $this->builderFactory->param('Hydrator')
//                                ->setTypeHint('TODO')
//                        )
                )
                ->addStmt(
                    $this->factory->method('__invoke')
                        ->makePublic()
                        ->setReturnType('string')
                        ->addParam(
                            $this->factory->param('object')
                                ->setTypeHint('object')
                        )
                        ->addParam(
                            $this->factory->param('context')
                                ->setTypeHint('\\' . DeserializationContext::class)
                        )
                        ->addStmts(
                            array_values(
                                array_map(
                                    function (PropertyMetadata $metadata) : Node {
                                        return (new Depth())->visit(
                                            $metadata,
                                            new Variable('context'),
                                            (new Group())->visit(
                                                $metadata,
                                                new Variable('context'),
                                                (new Direct())->visit(
                                                    $metadata,
                                                    new Variable('object'),
                                                    $this->factory
                                                ),
                                                $this->factory
                                            ),
                                            $this->factory
                                        );
                                    },
                                    $metadata->propertyMetadata
                                )
                            )
                        )
                )
            )
            ->getNode();
    }
}
