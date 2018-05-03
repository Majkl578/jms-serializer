<?php

declare(strict_types=1);

namespace JMS\Serializer\Tests\Metadata\Driver;

use JMS\Serializer\Metadata\ClassMetadataInterface;
use JMS\Serializer\Metadata\Driver\YamlDriver;
use JMS\Serializer\Metadata\PropertyMetadata;
use JMS\Serializer\Naming\IdenticalPropertyNamingStrategy;
use Metadata\Driver\FileLocator;

class YamlDriverTest extends BaseDriverTest
{
    public function testAccessorOrderIsInferred()
    {
        /** @var ClassMetadataInterface $m */
        $m = $this->getDriverForSubDir('accessor_inferred')->loadMetadataForClass(new \ReflectionClass('JMS\Serializer\Tests\Fixtures\Person'));
        self::assertEquals(['age', 'name'], array_keys($m->getProperties()));
    }

    public function testShortExposeSyntax()
    {
        /** @var ClassMetadataInterface $m */
        $m = $this->getDriverForSubDir('short_expose')->loadMetadataForClass(new \ReflectionClass('JMS\Serializer\Tests\Fixtures\Person'));

        self::assertArrayHasKey('name', $m->getProperties());
        self::assertArrayNotHasKey('age', $m->getProperties());
    }

    public function testBlogPost()
    {
        /** @var ClassMetadataInterface $m */
        $m = $this->getDriverForSubDir('exclude_all')->loadMetadataForClass(new \ReflectionClass('JMS\Serializer\Tests\Fixtures\BlogPost'));

        self::assertArrayHasKey('title', $m->getProperties());

        $excluded = ['createdAt', 'published', 'comments', 'author'];
        foreach ($excluded as $key) {
            self::assertArrayNotHasKey($key, $m->getProperties());
        }
    }

    public function testBlogPostExcludeNoneStrategy()
    {
        /** @var ClassMetadataInterface $m */
        $m = $this->getDriverForSubDir('exclude_none')->loadMetadataForClass(new \ReflectionClass('JMS\Serializer\Tests\Fixtures\BlogPost'));

        self::assertArrayNotHasKey('title', $m->getProperties());

        $excluded = ['createdAt', 'published', 'comments', 'author'];
        foreach ($excluded as $key) {
            self::assertArrayHasKey($key, $m->getProperties());
        }
    }

    public function testBlogPostCaseInsensitive()
    {
        /** @var ClassMetadataInterface $m */
        $m = $this->getDriverForSubDir('case')->loadMetadataForClass(new \ReflectionClass('JMS\Serializer\Tests\Fixtures\BlogPost'));

        $p = new PropertyMetadata($m->getName(), 'title');
        $p->serializedName = 'title';
        $p->type = ['name' => 'string', 'params' => []];
        self::assertEquals($p, $m->getProperties()['title']);
    }

    public function testBlogPostAccessor()
    {
        /** @var ClassMetadataInterface $m */
        $m = $this->getDriverForSubDir('accessor')->loadMetadataForClass(new \ReflectionClass('JMS\Serializer\Tests\Fixtures\BlogPost'));

        self::assertArrayHasKey('title', $m->getProperties());

        $p = new PropertyMetadata($m->getName(), 'title');
        $p->getter = 'getOtherTitle';
        $p->setter = 'setOtherTitle';
        $p->serializedName = 'title';
        self::assertEquals($p, $m->getProperties()['title']);
    }

    private function getDriverForSubDir($subDir = null)
    {
        return new YamlDriver(new FileLocator([
            'JMS\Serializer\Tests\Fixtures' => __DIR__ . '/yml' . ($subDir ? '/' . $subDir : ''),
        ]), new IdenticalPropertyNamingStrategy());
    }

    protected function getDriver()
    {
        return $this->getDriverForSubDir();
    }
}
