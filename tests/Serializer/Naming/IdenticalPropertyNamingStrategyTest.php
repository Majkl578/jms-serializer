<?php

declare(strict_types=1);

namespace JMS\Serializer\Tests\Serializer\Naming;

use JMS\Serializer\Metadata\PropertyMetadataInterface;
use JMS\Serializer\Naming\IdenticalPropertyNamingStrategy;

class IdenticalPropertyNamingStrategyTest extends \PHPUnit\Framework\TestCase
{
    public function providePropertyNames()
    {
        return [
            ['createdAt'],
            ['my_field'],
            ['identical']
        ];
    }

    /**
     * @dataProvider providePropertyNames
     */
    public function testTranslateName($propertyName)
    {
        $mockProperty = $this->getMockBuilder(PropertyMetadataInterface::class)->getMock();
        $mockProperty->method('getName')->willReturn($propertyName);

        $strategy = new IdenticalPropertyNamingStrategy();
        self::assertEquals($propertyName, $strategy->translateName($mockProperty));
    }
}
