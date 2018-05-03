<?php

declare(strict_types=1);

namespace JMS\Serializer\Tests\Metadata\Driver;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver as DoctrineDriver;
use JMS\Serializer\Metadata\ClassMetadataInterface;
use JMS\Serializer\Metadata\Driver\AnnotationDriver;
use JMS\Serializer\Metadata\Driver\DoctrineTypeDriver;
use JMS\Serializer\Naming\IdenticalPropertyNamingStrategy;

class DoctrineDriverTest extends \PHPUnit\Framework\TestCase
{
    public function getMetadata() : ClassMetadataInterface
    {
        $refClass = new \ReflectionClass('JMS\Serializer\Tests\Fixtures\Doctrine\BlogPost');
        $metadata = $this->getDoctrineDriver()->loadMetadataForClass($refClass);

        return $metadata;
    }

    public function testTypelessPropertyIsGivenTypeFromDoctrineMetadata()
    {
        $metadata = $this->getMetadata();

        self::assertEquals(
            ['name' => 'DateTime', 'params' => []],
            $metadata->getProperties()['createdAt']->type
        );
    }

    public function testSingleValuedAssociationIsProperlyHinted()
    {
        $metadata = $this->getMetadata();
        self::assertEquals(
            ['name' => 'JMS\Serializer\Tests\Fixtures\Doctrine\Author', 'params' => []],
            $metadata->getProperties()['author']->type
        );
    }

    public function testMultiValuedAssociationIsProperlyHinted()
    {
        $metadata = $this->getMetadata();

        self::assertEquals(
            ['name' => 'ArrayCollection', 'params' => [
                ['name' => 'JMS\Serializer\Tests\Fixtures\Doctrine\Comment', 'params' => []]]
            ],
            $metadata->getProperties()['comments']->type
        );
    }

    public function testTypeGuessByDoctrineIsOverwrittenByDelegateDriver()
    {
        $metadata = $this->getMetadata();

        // This would be guessed as boolean but we've overriden it to integer
        self::assertEquals(
            ['name' => 'integer', 'params' => []],
            $metadata->getProperties()['published']->type
        );
    }

    public function testUnknownDoctrineTypeDoesNotResultInAGuess()
    {
        $metadata = $this->getMetadata();
        self::assertNull($metadata->getProperties()['slug']->type);
    }

    public function testNonDoctrineEntityClassIsNotModified()
    {
        // Note: Using regular BlogPost fixture here instead of Doctrine fixture
        // because it has no Doctrine metadata.
        $refClass = new \ReflectionClass('JMS\Serializer\Tests\Fixtures\BlogPost');

        $plainMetadata = $this->getAnnotationDriver()->loadMetadataForClass($refClass);
        $doctrineMetadata = $this->getDoctrineDriver()->loadMetadataForClass($refClass);

        // Do not compare timestamps
        if (abs($doctrineMetadata->createdAt - $plainMetadata->createdAt) < 2) {
            $plainMetadata->createdAt = $doctrineMetadata->createdAt;
        }

        self::assertEquals($plainMetadata, $doctrineMetadata);
    }

    public function testExcludePropertyNoPublicAccessorException()
    {
        /** @var ClassMetadataInterface $first */
        $first = $this->getAnnotationDriver()
            ->loadMetadataForClass(new \ReflectionClass('JMS\Serializer\Tests\Fixtures\ExcludePublicAccessor'));

        self::assertArrayHasKey('id', $first->getProperties());
        self::assertArrayNotHasKey('iShallNotBeAccessed', $first->getProperties());
    }

    public function testVirtualPropertiesAreNotModified()
    {
        $doctrineMetadata = $this->getMetadata();
        self::assertNull($doctrineMetadata->getProperties()['ref']->type);
    }

    public function testGuidPropertyIsGivenStringType()
    {
        $metadata = $this->getMetadata();

        self::assertEquals(
            ['name' => 'string', 'params' => []],
            $metadata->getProperties()['id']->type
        );
    }

    protected function getEntityManager()
    {
        $config = new Configuration();
        $config->setProxyDir(sys_get_temp_dir() . '/JMSDoctrineTestProxies');
        $config->setProxyNamespace('JMS\Tests\Proxies');
        $config->setMetadataDriverImpl(
            new DoctrineDriver(new AnnotationReader(), __DIR__ . '/../../Fixtures/Doctrine')
        );

        $conn = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        return EntityManager::create($conn, $config);
    }

    public function getAnnotationDriver()
    {
        return new AnnotationDriver(new AnnotationReader(), new IdenticalPropertyNamingStrategy());
    }

    protected function getDoctrineDriver()
    {
        $registry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')->getMock();
        $registry->expects($this->atLeastOnce())
            ->method('getManagerForClass')
            ->will($this->returnValue($this->getEntityManager()));

        return new DoctrineTypeDriver(
            $this->getAnnotationDriver(),
            $registry
        );
    }
}
