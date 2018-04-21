<?php

declare(strict_types=1);

namespace JMSTest;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Marshaller\Generator\Exporter\FileExporter;
use JMS\Serializer\Marshaller\Generator\MarshallerGenerator;
use JMS\Serializer\SerializerBuilder;
use PhpParser\PrettyPrinter\Standard;

require __DIR__ . '/vendor/autoload.php';

AnnotationRegistry::registerUniqueLoader('class_exists');

$builder = new SerializerBuilder();
$builder->setAnnotationReader(new AnnotationReader());

$serializer = $builder->build();
$metadataFactory = $serializer->getMetadataFactory();

class Test
{
    private $foo;

    /**
     * @Serializer\SerializedName("_bar_")
     */
    private $bar;

    /**
     * @Serializer\MaxDepth(1)
     */
    private $baz;

    /**
     * @Serializer\Groups({"Blah"})
     */
    private $bax;
}

$metadata = $metadataFactory->getMetadataForClass(Test::class);

$node = (new MarshallerGenerator())($metadata);

(new FileExporter(new Standard()))->export($node);
