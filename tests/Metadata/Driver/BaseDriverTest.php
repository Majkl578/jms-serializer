<?php

declare(strict_types=1);

namespace JMS\Serializer\Tests\Metadata\Driver;

use JMS\Serializer\Metadata\ClassMetadataInterface;
use JMS\Serializer\Metadata\ExpressionPropertyMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;
use JMS\Serializer\Metadata\PropertyMetadataInterface;
use JMS\Serializer\Metadata\VirtualPropertyMetadata;
use JMS\Serializer\Tests\Fixtures\Discriminator\ObjectWithXmlAttributeDiscriminatorChild;
use JMS\Serializer\Tests\Fixtures\Discriminator\ObjectWithXmlAttributeDiscriminatorParent;
use JMS\Serializer\Tests\Fixtures\Discriminator\ObjectWithXmlNamespaceAttributeDiscriminatorChild;
use JMS\Serializer\Tests\Fixtures\Discriminator\ObjectWithXmlNamespaceAttributeDiscriminatorParent;
use JMS\Serializer\Tests\Fixtures\Discriminator\ObjectWithXmlNamespaceDiscriminatorChild;
use JMS\Serializer\Tests\Fixtures\Discriminator\ObjectWithXmlNamespaceDiscriminatorParent;
use JMS\Serializer\Tests\Fixtures\FirstClassListCollection;
use JMS\Serializer\Tests\Fixtures\FirstClassMapCollection;
use JMS\Serializer\Tests\Fixtures\ObjectWithVirtualPropertiesAndDuplicatePropName;
use JMS\Serializer\Tests\Fixtures\ParentSkipWithEmptyChild;
use Metadata\Driver\DriverInterface;

abstract class BaseDriverTest extends \PHPUnit\Framework\TestCase
{
    public function testLoadBlogPostMetadata()
    {
        /** @var ClassMetadataInterface $m */
        $m = $this->getDriver()->loadMetadataForClass(new \ReflectionClass('JMS\Serializer\Tests\Fixtures\BlogPost'));

        self::assertNotNull($m);
        self::assertEquals('blog-post', $m->getXmlRootName());
        self::assertCount(4, $m->getXmlNamespaces());
        self::assertArrayHasKey('', $m->getXmlNamespaces());
        self::assertEquals('http://example.com/namespace', $m->getXmlNamespaces()['']);
        self::assertArrayHasKey('gd', $m->getXmlNamespaces());
        self::assertEquals('http://schemas.google.com/g/2005', $m->getXmlNamespaces()['gd']);
        self::assertArrayHasKey('atom', $m->getXmlNamespaces());
        self::assertEquals('http://www.w3.org/2005/Atom', $m->getXmlNamespaces()['atom']);
        self::assertArrayHasKey('dc', $m->getXmlNamespaces());
        self::assertEquals('http://purl.org/dc/elements/1.1/', $m->getXmlNamespaces()['dc']);

        self::assertFalse($m->isList());
        self::assertFalse($m->isMap());

        $p = new PropertyMetadata($m->getName(), 'id');
        $p->type = ['name' => 'string', 'params' => []];
        $p->groups = ["comments", "post"];
        $p->serializedName = 'id';
        $p->xmlElementCData = false;
        self::assertEquals($p, $m->getProperties()['id']);

        $p = new PropertyMetadata($m->getName(), 'title');
        $p->type = ['name' => 'string', 'params' => []];
        $p->serializedName = 'title';
        $p->groups = ["comments", "post"];
        $p->xmlNamespace = "http://purl.org/dc/elements/1.1/";
        self::assertEquals($p, $m->getProperties()['title']);

        $p = new PropertyMetadata($m->getName(), 'createdAt');
        $p->type = ['name' => 'DateTime', 'params' => []];
        $p->serializedName = 'createdAt';
        $p->xmlAttribute = true;
        self::assertEquals($p, $m->getProperties()['createdAt']);

        $p = new PropertyMetadata($m->getName(), 'published');
        $p->serializedName = 'published';
        $p->type = ['name' => 'boolean', 'params' => []];
        $p->serializedName = 'is_published';
        $p->xmlAttribute = true;
        $p->groups = ["post"];
        self::assertEquals($p, $m->getProperties()['published']);

        $p = new PropertyMetadata($m->getName(), 'etag');
        $p->serializedName = 'etag';
        $p->type = ['name' => 'string', 'params' => []];
        $p->xmlAttribute = true;
        $p->groups = ["post"];
        $p->xmlNamespace = "http://schemas.google.com/g/2005";
        self::assertEquals($p, $m->getProperties()['etag']);

        $p = new PropertyMetadata($m->getName(), 'comments');
        $p->serializedName = 'comments';
        $p->type = ['name' => 'ArrayCollection', 'params' => [['name' => 'JMS\Serializer\Tests\Fixtures\Comment', 'params' => []]]];
        $p->xmlCollection = true;
        $p->xmlCollectionInline = true;
        $p->xmlEntryName = 'comment';
        $p->groups = ["comments"];
        self::assertEquals($p, $m->getProperties()['comments']);

        $p = new PropertyMetadata($m->getName(), 'author');
        $p->serializedName = 'author';
        $p->type = ['name' => 'JMS\Serializer\Tests\Fixtures\Author', 'params' => []];
        $p->groups = ["post"];
        $p->xmlNamespace = 'http://www.w3.org/2005/Atom';
        self::assertEquals($p, $m->getProperties()['author']);

        $m = $this->getDriver()->loadMetadataForClass(new \ReflectionClass('JMS\Serializer\Tests\Fixtures\Price'));
        self::assertNotNull($m);

        $p = new PropertyMetadata($m->getName(), 'price');
        $p->serializedName = 'price';
        $p->type = ['name' => 'float', 'params' => []];
        $p->xmlValue = true;
        self::assertEquals($p, $m->getProperties()['price']);
    }

    public function testXMLListAbsentNode()
    {
        /** @var ClassMetadataInterface $m */
        $m = $this->getDriver()->loadMetadataForClass(new \ReflectionClass('JMS\Serializer\Tests\Fixtures\ObjectWithAbsentXmlListNode'));

        self::assertArrayHasKey('absent', $m->getProperties());
        self::assertArrayHasKey('present', $m->getProperties());
        self::assertArrayHasKey('skipDefault', $m->getProperties());

        self::assertTrue($m->getProperties()['absent']->isXmlCollectionSkippedWhenEmpty());
        self::assertTrue($m->getProperties()['skipDefault']->isXmlCollectionSkippedWhenEmpty());
        self::assertFalse($m->getProperties()['present']->isXmlCollectionSkippedWhenEmpty());
    }

    public function testVirtualProperty()
    {
        /** @var ClassMetadataInterface $m */
        $m = $this->getDriver()->loadMetadataForClass(new \ReflectionClass('JMS\Serializer\Tests\Fixtures\ObjectWithVirtualProperties'));

        self::assertArrayHasKey('existField', $m->getProperties());
        self::assertArrayHasKey('virtualValue', $m->getProperties());
        self::assertArrayHasKey('virtualSerializedValue', $m->getProperties());
        self::assertArrayHasKey('typedVirtualProperty', $m->getProperties());

        self::assertEquals($m->getProperties()['virtualSerializedValue']->getSerializedName(), 'test', 'Serialized name is missing');

        $p = new VirtualPropertyMetadata($m->getName(), 'virtualValue');
        $p->getter = 'getVirtualValue';
        $p->serializedName = 'virtualValue';

        self::assertEquals($p, $m->getProperties()['virtualValue']);
    }

    public function testFirstClassListCollection()
    {
        /** @var ClassMetadataInterface $m */
        $m = $this->getDriver()->loadMetadataForClass(new \ReflectionClass(FirstClassListCollection::class));
        self::assertTrue($m->isList());
        self::assertFalse($m->isMap());
    }

    public function testFirstClassMapCollection()
    {
        /** @var ClassMetadataInterface $m */
        $m = $this->getDriver()->loadMetadataForClass(new \ReflectionClass(FirstClassMapCollection::class));
        self::assertFalse($m->isList());
        self::assertTrue($m->isMap());
    }

    public function testXmlKeyValuePairs()
    {
        /** @var ClassMetadataInterface $m */
        $m = $this->getDriver()->loadMetadataForClass(new \ReflectionClass('JMS\Serializer\Tests\Fixtures\ObjectWithXmlKeyValuePairs'));

        self::assertArrayHasKey('array', $m->getProperties());
        self::assertTrue($m->getProperties()['array']->isXmlKeyValuePairs());
    }

    public function testExpressionVirtualPropertyWithExcludeAll()
    {
        $a = new \JMS\Serializer\Tests\Fixtures\ObjectWithExpressionVirtualPropertiesAndExcludeAll();

        /** @var ClassMetadataInterface $m */
        $m = $this->getDriver()->loadMetadataForClass(new \ReflectionClass($a));;

        self::assertArrayHasKey('virtualValue', $m->getProperties());

        $p = new ExpressionPropertyMetadata($m->getName(), 'virtualValue', 'object.getVirtualValue()');
        $p->serializedName = 'virtualValue';
        self::assertEquals($p, $m->getProperties()['virtualValue']);
    }

    public function testVirtualPropertyWithExcludeAll()
    {
        $a = new \JMS\Serializer\Tests\Fixtures\ObjectWithVirtualPropertiesAndExcludeAll();

        /** @var ClassMetadataInterface $m */
        $m = $this->getDriver()->loadMetadataForClass(new \ReflectionClass($a));

        self::assertArrayHasKey('virtualValue', $m->getProperties());

        $p = new VirtualPropertyMetadata($m->getName(), 'virtualValue');
        $p->getter = 'getVirtualValue';
        $p->serializedName = 'virtualValue';

        self::assertEquals($p, $m->getProperties()['virtualValue']);
    }

    public function testReadOnlyDefinedBeforeGetterAndSetter()
    {
        $m = $this->getDriver()->loadMetadataForClass(new \ReflectionClass('JMS\Serializer\Tests\Fixtures\AuthorReadOnly'));

        self::assertNotNull($m);
    }

    public function testExpressionVirtualProperty()
    {
        /** @var $m ClassMetadataInterface */
        $m = $this->getDriver()->loadMetadataForClass(new \ReflectionClass('JMS\Serializer\Tests\Fixtures\AuthorExpressionAccess'));

        $keys = array_keys($m->getProperties());
        self::assertEquals(['firstName', 'lastName', 'id'], $keys);
    }

    public function testLoadDiscriminator()
    {
        /** @var $m ClassMetadataInterface */
        $m = $this->getDriver()->loadMetadataForClass(new \ReflectionClass('JMS\Serializer\Tests\Fixtures\Discriminator\Vehicle'));

        self::assertNotNull($m);
        self::assertEquals('type', $m->getDiscriminatorFieldName());
        self::assertEquals($m->getName(), $m->getDiscriminatorBaseClass());
        self::assertEquals(
            [
                'car' => 'JMS\Serializer\Tests\Fixtures\Discriminator\Car',
                'moped' => 'JMS\Serializer\Tests\Fixtures\Discriminator\Moped',
            ],
            $m->getDiscriminatorMap()
        );
    }

    public function testLoadXmlDiscriminator()
    {
        /** @var $m ClassMetadataInterface */
        $m = $this->getDriver()->loadMetadataForClass(new \ReflectionClass(ObjectWithXmlAttributeDiscriminatorParent::class));

        self::assertNotNull($m);
        self::assertEquals('type', $m->getDiscriminatorFieldName());
        self::assertEquals($m->getName(), $m->getDiscriminatorBaseClass());
        self::assertEquals(
            [
                'child' => ObjectWithXmlAttributeDiscriminatorChild::class,
            ],
            $m->getDiscriminatorMap()
        );
        self::assertTrue($m->getsXmlDiscriminatorAttribute());
        self::assertFalse($m->getXmlDiscriminatorCData());
    }

    public function testLoadXmlDiscriminatorWithNamespaces()
    {
        /** @var $m ClassMetadataInterface */
        $m = $this->getDriver()->loadMetadataForClass(new \ReflectionClass(ObjectWithXmlNamespaceDiscriminatorParent::class));

        self::assertNotNull($m);
        self::assertEquals('type', $m->getDiscriminatorFieldName());
        self::assertEquals($m->getName(), $m->getDiscriminatorBaseClass());
        self::assertEquals(
            [
                'child' => ObjectWithXmlNamespaceDiscriminatorChild::class,
            ],
            $m->getDiscriminatorMap()
        );
        self::assertEquals('http://example.com/', $m->getXmlDiscriminatorNamespace());
        self::assertFalse($m->getsXmlDiscriminatorAttribute());
    }

    public function testLoadXmlDiscriminatorWithAttributeNamespaces()
    {
        /** @var $m ClassMetadataInterface */
        $m = $this->getDriver()->loadMetadataForClass(new \ReflectionClass(ObjectWithXmlNamespaceAttributeDiscriminatorParent::class));

        self::assertNotNull($m);
        self::assertEquals('type', $m->getDiscriminatorFieldName());
        self::assertEquals($m->getName(), $m->getDiscriminatorBaseClass());
        self::assertEquals(
            [
                'child' => ObjectWithXmlNamespaceAttributeDiscriminatorChild::class,
            ],
            $m->getDiscriminatorMap()
        );
        self::assertEquals('http://example.com/', $m->getXmlDiscriminatorNamespace());
        self::assertTrue($m->getsXmlDiscriminatorAttribute());
    }

    public function testLoadDiscriminatorWithGroup()
    {
        /** @var $m ClassMetadataInterface */
        $m = $this->getDriver()->loadMetadataForClass(new \ReflectionClass('JMS\Serializer\Tests\Fixtures\DiscriminatorGroup\Vehicle'));

        self::assertNotNull($m);
        self::assertEquals('type', $m->getDiscriminatorFieldName());
        self::assertEquals(['foo'], $m->getDiscriminatorGroups());
        self::assertEquals($m->getName(), $m->getDiscriminatorBaseClass());
        self::assertEquals(
            [
                'car' => 'JMS\Serializer\Tests\Fixtures\DiscriminatorGroup\Car'
            ],
            $m->getDiscriminatorMap()
        );
    }

    public function testSkipWhenEmptyOption()
    {
        /** @var $m ClassMetadataInterface */
        $m = $this->getDriver()->loadMetadataForClass(new \ReflectionClass(ParentSkipWithEmptyChild::class));

        self::assertNotNull($m);

        self::assertInstanceOf(PropertyMetadata::class, $m->getProperties()['c']);
        self::assertInstanceOf(PropertyMetadata::class, $m->getProperties()['d']);
        self::assertInstanceOf(PropertyMetadata::class, $m->getProperties()['child']);
        self::assertFalse($m->getProperties()['c']->isSkippedWhenEmpty());
        self::assertFalse($m->getProperties()['d']->isSkippedWhenEmpty());
        self::assertTrue($m->getProperties()['child']->isSkippedWhenEmpty());
    }

    public function testLoadDiscriminatorSubClass()
    {
        /** @var $m ClassMetadataInterface */
        $m = $this->getDriver()->loadMetadataForClass(new \ReflectionClass('JMS\Serializer\Tests\Fixtures\Discriminator\Car'));

        self::assertNotNull($m);
        self::assertNull($m->getDiscriminatorValue());
        self::assertNull($m->getDiscriminatorBaseClass());
        self::assertNull($m->getDiscriminatorFieldName());
        self::assertEquals([], $m->getDiscriminatorMap());
    }

    public function testLoadXmlObjectWithNamespacesMetadata()
    {
        /** @var ClassMetadataInterface $m */
        $m = $this->getDriver()->loadMetadataForClass(new \ReflectionClass('JMS\Serializer\Tests\Fixtures\ObjectWithXmlNamespaces'));

        self::assertNotNull($m);
        self::assertEquals('test-object', $m->getXmlRootName());
        self::assertEquals('ex', $m->getXmlRootPrefix());
        self::assertEquals('http://example.com/namespace', $m->getXmlRootNamespace());
        self::assertCount(3, $m->getXmlNamespaces());
        self::assertArrayHasKey('', $m->getXmlNamespaces());
        self::assertEquals('http://example.com/namespace', $m->getXmlNamespaces()['']);
        self::assertArrayHasKey('gd', $m->getXmlNamespaces());
        self::assertEquals('http://schemas.google.com/g/2005', $m->getXmlNamespaces()['gd']);
        self::assertArrayHasKey('atom', $m->getXmlNamespaces());
        self::assertEquals('http://www.w3.org/2005/Atom', $m->getXmlNamespaces()['atom']);

        $p = new PropertyMetadata($m->getName(), 'title');
        $p->serializedName = 'title';
        $p->type = ['name' => 'string', 'params' => []];
        $p->xmlNamespace = "http://purl.org/dc/elements/1.1/";
        self::assertEquals($p, $m->getProperties()['title']);

        $p = new PropertyMetadata($m->getName(), 'createdAt');
        $p->serializedName = 'createdAt';
        $p->type = ['name' => 'DateTime', 'params' => []];
        $p->xmlAttribute = true;
        self::assertEquals($p, $m->getProperties()['createdAt']);

        $p = new PropertyMetadata($m->getName(), 'etag');
        $p->serializedName = 'etag';
        $p->type = ['name' => 'string', 'params' => []];
        $p->xmlAttribute = true;
        $p->xmlNamespace = "http://schemas.google.com/g/2005";
        self::assertEquals($p, $m->getProperties()['etag']);

        $p = new PropertyMetadata($m->getName(), 'author');
        $p->serializedName = 'author';
        $p->type = ['name' => 'string', 'params' => []];
        $p->xmlAttribute = false;
        $p->xmlNamespace = "http://www.w3.org/2005/Atom";
        self::assertEquals($p, $m->getProperties()['author']);

        $p = new PropertyMetadata($m->getName(), 'language');
        $p->serializedName = 'language';
        $p->type = ['name' => 'string', 'params' => []];
        $p->xmlAttribute = true;
        $p->xmlNamespace = "http://purl.org/dc/elements/1.1/";
        self::assertEquals($p, $m->getProperties()['language']);
    }

    public function testMaxDepth()
    {
        /** @var ClassMetadataInterface $m */
        $m = $this->getDriver()->loadMetadataForClass(new \ReflectionClass('JMS\Serializer\Tests\Fixtures\Node'));

        self::assertEquals(2, $m->getProperties()['children']->getMaxDepth());
    }

    public function testPersonCData()
    {
        /** @var ClassMetadataInterface $m */
        $m = $this->getDriver()->loadMetadataForClass(new \ReflectionClass('JMS\Serializer\Tests\Fixtures\Person'));

        self::assertNotNull($m);
        self::assertFalse($m->getProperties()['name']->isXmlElementCData());
    }

    public function testXmlNamespaceInheritanceMetadata()
    {
        /** @var ClassMetadataInterface $m */
        $m = $this->getDriver()->loadMetadataForClass(new \ReflectionClass('JMS\Serializer\Tests\Fixtures\SimpleClassObject'));

        self::assertNotNull($m);
        self::assertCount(3, $m->getXmlNamespaces());
        self::assertArrayHasKey('old_foo', $m->getXmlNamespaces());
        self::assertEquals('http://old.foo.example.org', $m->getXmlNamespaces()['old_foo']);
        self::assertArrayHasKey('foo', $m->getXmlNamespaces());
        self::assertEquals('http://foo.example.org', $m->getXmlNamespaces()['foo']);
        self::assertArrayHasKey('new_foo', $m->getXmlNamespaces());
        self::assertEquals('http://new.foo.example.org', $m->getXmlNamespaces()['new_foo']);
        self::assertCount(3, $m->getProperties());

        $p = new PropertyMetadata($m->getName(), 'foo');
        $p->serializedName = 'foo';
        $p->type = ['name' => 'string', 'params' => []];
        $p->xmlNamespace = "http://old.foo.example.org";
        $p->xmlAttribute = true;
        self::assertEquals($p, $m->getProperties()['foo']);

        $p = new PropertyMetadata($m->getName(), 'bar');
        $p->serializedName = 'bar';
        $p->type = ['name' => 'string', 'params' => []];
        $p->xmlNamespace = "http://foo.example.org";
        self::assertEquals($p, $m->getProperties()['bar']);

        $p = new PropertyMetadata($m->getName(), 'moo');
        $p->serializedName = 'moo';
        $p->type = ['name' => 'string', 'params' => []];
        $p->xmlNamespace = "http://new.foo.example.org";
        self::assertEquals($p, $m->getProperties()['moo']);

        /** @var ClassMetadataInterface $subm */
        $subm = $this->getDriver()->loadMetadataForClass(new \ReflectionClass('JMS\Serializer\Tests\Fixtures\SimpleSubClassObject'));

        self::assertNotNull($subm);
        self::assertCount(2, $subm->getXmlNamespaces());
        self::assertArrayHasKey('old_foo', $subm->getXmlNamespaces());
        self::assertEquals('http://foo.example.org', $subm->getXmlNamespaces()['old_foo']);
        self::assertArrayHasKey('foo', $subm->getXmlNamespaces());
        self::assertEquals('http://better.foo.example.org', $subm->getXmlNamespaces()['foo']);
        self::assertCount(3, $subm->getProperties());

        $p = new PropertyMetadata($subm->getName(), 'moo');
        $p->serializedName = 'moo';
        $p->type = ['name' => 'string', 'params' => []];
        $p->xmlNamespace = "http://better.foo.example.org";
        self::assertEquals($p, $subm->getProperties()['moo']);

        $p = new PropertyMetadata($subm->getName(), 'baz');
        $p->serializedName = 'baz';
        $p->type = ['name' => 'string', 'params' => []];
        $p->xmlNamespace = "http://foo.example.org";
        self::assertEquals($p, $subm->getProperties()['baz']);

        $p = new PropertyMetadata($subm->getName(), 'qux');
        $p->serializedName = 'qux';
        $p->type = ['name' => 'string', 'params' => []];
        $p->xmlNamespace = "http://new.foo.example.org";
        self::assertEquals($p, $subm->getProperties()['qux']);

        $m->merge($subm);
        self::assertNotNull($m);
        self::assertCount(3, $m->getXmlNamespaces());
        self::assertArrayHasKey('old_foo', $m->getXmlNamespaces());
        self::assertEquals('http://foo.example.org', $m->getXmlNamespaces()['old_foo']);
        self::assertArrayHasKey('foo', $m->getXmlNamespaces());
        self::assertEquals('http://better.foo.example.org', $m->getXmlNamespaces()['foo']);
        self::assertArrayHasKey('new_foo', $m->getXmlNamespaces());
        self::assertEquals('http://new.foo.example.org', $m->getXmlNamespaces()['new_foo']);
        self::assertCount(5, $m->getProperties());

        $p = new PropertyMetadata($m->getName(), 'foo');
        $p->serializedName = 'foo';
        $p->type = ['name' => 'string', 'params' => []];
        $p->xmlNamespace = "http://old.foo.example.org";
        $p->xmlAttribute = true;
        $p->class = 'JMS\Serializer\Tests\Fixtures\SimpleClassObject';
        $this->assetMetadataEquals($p, $m->getProperties()['foo']);

        $p = new PropertyMetadata($m->getName(), 'bar');
        $p->serializedName = 'bar';
        $p->type = ['name' => 'string', 'params' => []];
        $p->xmlNamespace = "http://foo.example.org";
        $p->class = 'JMS\Serializer\Tests\Fixtures\SimpleClassObject';
        $this->assetMetadataEquals($p, $m->getProperties()['bar']);

        $p = new PropertyMetadata($m->getName(), 'moo');
        $p->serializedName = 'moo';
        $p->type = ['name' => 'string', 'params' => []];
        $p->xmlNamespace = "http://better.foo.example.org";
        $this->assetMetadataEquals($p, $m->getProperties()['moo']);

        $p = new PropertyMetadata($m->getName(), 'baz');
        $p->serializedName = 'baz';
        $p->type = ['name' => 'string', 'params' => []];
        $p->xmlNamespace = "http://foo.example.org";
        $this->assetMetadataEquals($p, $m->getProperties()['baz']);

        $p = new PropertyMetadata($m->getName(), 'qux');
        $p->serializedName = 'qux';
        $p->type = ['name' => 'string', 'params' => []];
        $p->xmlNamespace = "http://new.foo.example.org";
        $this->assetMetadataEquals($p, $m->getProperties()['qux']);
    }

    private function assetMetadataEquals(PropertyMetadataInterface $expected, PropertyMetadataInterface $actual)
    {
        $expectedVars = get_object_vars($expected);
        $actualVars = get_object_vars($actual);

        self::assertEquals($expectedVars, $actualVars);
    }

    public function testExclusionIf()
    {
        $class = 'JMS\Serializer\Tests\Fixtures\PersonSecret';

        /** @var ClassMetadataInterface $m */
        $m = $this->getDriver()->loadMetadataForClass(new \ReflectionClass($class));

        $p = new PropertyMetadata($class, 'name');
        $p->serializedName = 'name';
        $p->type = ['name' => 'string', 'params' => []];
        self::assertEquals($p, $m->getProperties()['name']);

        $p = new PropertyMetadata($class, 'gender');
        $p->serializedName = 'gender';
        $p->type = ['name' => 'string', 'params' => []];
        $p->excludeIf = "show_data('gender')";
        self::assertEquals($p, $m->getProperties()['gender']);

        $p = new PropertyMetadata($class, 'age');
        $p->serializedName = 'age';
        $p->type = ['name' => 'string', 'params' => []];
        $p->excludeIf = "!(show_data('age'))";
        self::assertEquals($p, $m->getProperties()['age']);
    }

    public function testObjectWithVirtualPropertiesAndDuplicatePropName()
    {
        $class = ObjectWithVirtualPropertiesAndDuplicatePropName::class;

        /** @var ClassMetadataInterface $m */
        $m = $this->getDriver()->loadMetadataForClass(new \ReflectionClass($class));

        $p = new PropertyMetadata($class, 'id');
        $p->serializedName = 'id';
        self::assertEquals($p, $m->getProperties()['id']);

        $p = new PropertyMetadata($class, 'name');
        $p->serializedName = 'name';
        self::assertEquals($p, $m->getProperties()['name']);

        $p = new VirtualPropertyMetadata($class, 'foo');
        $p->serializedName = 'id';
        $p->getter = 'getId';

        self::assertEquals($p, $m->getProperties()['foo']);

        $p = new VirtualPropertyMetadata($class, 'bar');
        $p->serializedName = 'mood';
        $p->getter = 'getName';

        self::assertEquals($p, $m->getProperties()['bar']);
    }

    public function testExcludePropertyNoPublicAccessorException()
    {
        /** @var ClassMetadataInterface $first */
        $first = $this->getDriver()->loadMetadataForClass(new \ReflectionClass('JMS\Serializer\Tests\Fixtures\ExcludePublicAccessor'));

        if ($this instanceof PhpDriverTest) {
            return;
        }
        self::assertArrayHasKey('id', $first->getProperties());
        self::assertArrayNotHasKey('iShallNotBeAccessed', $first->getProperties());
    }

    /**
     * @return DriverInterface
     */
    abstract protected function getDriver();
}
