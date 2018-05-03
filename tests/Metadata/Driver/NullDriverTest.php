<?php

declare(strict_types=1);

namespace JMS\Serializer\Tests\Metadata\Driver;

use JMS\Serializer\Metadata\ClassMetadataInterface;
use JMS\Serializer\Metadata\Driver\NullDriver;

class NullDriverTest extends \PHPUnit\Framework\TestCase
{
    public function testReturnsValidMetadata()
    {
        $driver = new NullDriver();

        /** @var ClassMetadataInterface $metadata */
        $metadata = $driver->loadMetadataForClass(new \ReflectionClass('stdClass'));

        self::assertInstanceOf(ClassMetadataInterface::class, $metadata);
        self::assertCount(0, $metadata->getProperties());
    }
}
