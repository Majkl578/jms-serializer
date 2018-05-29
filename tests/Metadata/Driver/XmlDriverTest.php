<?php

declare(strict_types=1);

namespace JMS\Serializer\Tests\Metadata\Driver;

use JMS\Serializer\Metadata\ClassMetadataInterface;
use JMS\Serializer\Metadata\Driver\XmlDriver;
use JMS\Serializer\Metadata\PropertyMetadata;
use JMS\Serializer\Naming\IdenticalPropertyNamingStrategy;
use Metadata\Driver\FileLocator;

class XmlDriverTest extends BaseDriverTest
{
    /**
     * @expectedException \JMS\Serializer\Exception\InvalidMetadataException
     * @expectedExceptionMessage Invalid XML content for metadata
     */
    public function testInvalidXml()
    {
        $driver = $this->getDriver();

        $ref = new \ReflectionMethod($driver, 'loadMetadataFromFile');
        $ref->setAccessible(true);
        $ref->invoke($driver, new \ReflectionClass('stdClass'), __DIR__ . '/xml/invalid.xml');
    }

    public function testBlogPostExcludeAllStrategy()
    {
        /** @var ClassMetadataInterface $m */
        $m = $this->getDriver('exclude_all')->loadMetadataForClass(new \ReflectionClass('JMS\Serializer\Tests\Fixtures\BlogPost'));

        self::assertArrayHasKey('title', $m->getProperties());

        $excluded = ['createdAt', 'published', 'comments', 'author'];
        foreach ($excluded as $key) {
            self::assertArrayNotHasKey($key, $m->getProperties());
        }
    }

    public function testBlogPostExcludeNoneStrategy()
    {
        /** @var ClassMetadataInterface $m */
        $m = $this->getDriver('exclude_none')->loadMetadataForClass(new \ReflectionClass('JMS\Serializer\Tests\Fixtures\BlogPost'));

        self::assertArrayNotHasKey('title', $m->getProperties());

        $excluded = ['createdAt', 'published', 'comments', 'author'];
        foreach ($excluded as $key) {
            self::assertArrayHasKey($key, $m->getProperties());
        }
    }

    public function testBlogPostCaseInsensitive()
    {
        /** @var ClassMetadataInterface $m */
        $m = $this->getDriver('case')->loadMetadataForClass(new \ReflectionClass('JMS\Serializer\Tests\Fixtures\BlogPost'));

        $p = new PropertyMetadata($m->getName(), 'title');
        $p->serializedName = 'title';
        $p->type = ['name' => 'string', 'params' => []];
        self::assertEquals($p, $m->getProperties()['title']);
    }

    public function testAccessorAttributes()
    {
        /** @var ClassMetadataInterface $m */
        $m = $this->getDriver()->loadMetadataForClass(new \ReflectionClass('JMS\Serializer\Tests\Fixtures\GetSetObject'));

        $p = new PropertyMetadata($m->getName(), 'name');
        $p->type = ['name' => 'string', 'params' => []];
        $p->getter = 'getTrimmedName';
        $p->setter = 'setCapitalizedName';
        $p->serializedName = 'name';

        self::assertEquals($p, $m->getProperties()['name']);
    }

    public function testGroupsTrim()
    {
        /** @var ClassMetadataInterface $first */
        $first = $this->getDriver()->loadMetadataForClass(new \ReflectionClass('JMS\Serializer\Tests\Fixtures\GroupsTrim'));

        self::assertArrayHasKey('amount', $first->getProperties());
        self::assertArraySubset(['first.test.group', 'second.test.group'], $first->getProperties()['currency']->getGroups());
    }

    public function testMultilineGroups()
    {
        /** @var ClassMetadataInterface $first */
        $first = $this->getDriver()->loadMetadataForClass(new \ReflectionClass('JMS\Serializer\Tests\Fixtures\MultilineGroupsFormat'));

        self::assertArrayHasKey('amount', $first->getProperties());
        self::assertArraySubset(['first.test.group', 'second.test.group'], $first->getProperties()['currency']->getGroups());
    }

    protected function getDriver()
    {
        $append = '';
        if (func_num_args() == 1) {
            $append = '/' . func_get_arg(0);
        }

        return new XmlDriver(new FileLocator([
            'JMS\Serializer\Tests\Fixtures' => __DIR__ . '/xml' . $append,
        ]), new IdenticalPropertyNamingStrategy());
    }
}
