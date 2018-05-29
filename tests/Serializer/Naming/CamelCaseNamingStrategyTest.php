<?php

declare(strict_types=1);

namespace JMS\Serializer\Tests\Serializer\Naming;

use JMS\Serializer\Metadata\PropertyMetadataInterface;
use JMS\Serializer\Naming\CamelCaseNamingStrategy;

class CamelCaseNamingStrategyTest extends \PHPUnit\Framework\TestCase
{

    public function providePropertyNames()
    {
        return [
            ['getUrl', 'get_url'],
            ['getURL', 'get_url']
        ];
    }

    /**
     * @dataProvider providePropertyNames
     */
    public function testCamelCaseNamingHandlesMultipleUppercaseLetters($propertyName, $expected)
    {
        $mockProperty = $this->getMockBuilder(PropertyMetadataInterface::class)->getMock();
        $mockProperty->method('getName')->willReturn($propertyName);

        $strategy = new CamelCaseNamingStrategy();
        self::assertEquals($expected, $strategy->translateName($mockProperty));
    }

}
