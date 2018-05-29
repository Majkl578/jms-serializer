<?php

declare(strict_types=1);

namespace JMS\Serializer;

use JMS\Serializer\Exception\InvalidArgumentException;
use JMS\Serializer\Exception\LogicException;
use JMS\Serializer\Exception\NotAcceptableException;
use JMS\Serializer\Exception\RuntimeException;
use JMS\Serializer\Exception\XmlErrorException;
use JMS\Serializer\Metadata\ClassMetadataInterface;
use JMS\Serializer\Metadata\PropertyMetadataInterface;
use JMS\Serializer\Visitor\DeserializationVisitorInterface;

final class XmlDeserializationVisitor extends AbstractVisitor implements NullAwareVisitorInterface, DeserializationVisitorInterface
{
    private $objectStack;
    private $metadataStack;
    private $objectMetadataStack;
    private $currentObject;

    /** @var PropertyMetadataInterface */
    private $currentMetadata;
    private $disableExternalEntities = true;
    private $doctypeWhitelist = [];

    public function __construct(
        bool $disableExternalEntities = true, array $doctypeWhitelist = [])
    {
        $this->objectStack = new \SplStack;
        $this->metadataStack = new \SplStack;
        $this->objectMetadataStack = new \SplStack;
        $this->disableExternalEntities = $disableExternalEntities;
        $this->doctypeWhitelist = $doctypeWhitelist;
    }

    public function prepare($data)
    {
        $data = $this->emptyStringToSpaceCharacter($data);

        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();

        $previousEntityLoaderState = libxml_disable_entity_loader($this->disableExternalEntities);

        if (false !== stripos($data, '<!doctype')) {
            $internalSubset = $this->getDomDocumentTypeEntitySubset($data);
            if (!in_array($internalSubset, $this->doctypeWhitelist, true)) {
                throw new InvalidArgumentException(sprintf(
                    'The document type "%s" is not allowed. If it is safe, you may add it to the whitelist configuration.',
                    $internalSubset
                ));
            }
        }

        $doc = simplexml_load_string($data);

        libxml_use_internal_errors($previous);
        libxml_disable_entity_loader($previousEntityLoaderState);

        if (false === $doc) {
            throw new XmlErrorException(libxml_get_last_error());
        }

        return $doc;
    }

    private function emptyStringToSpaceCharacter($data)
    {
        return $data === '' ? ' ' : (string)$data;
    }

    public function visitNull($data, array $type): void
    {

    }

    public function visitString($data, array $type): string
    {
        return (string)$data;
    }

    public function visitBoolean($data, array $type): bool
    {
        $data = (string)$data;

        if ('true' === $data || '1' === $data) {
            return true;
        } elseif ('false' === $data || '0' === $data) {
            return false;
        } else {
            throw new RuntimeException(sprintf('Could not convert data to boolean. Expected "true", "false", "1" or "0", but got %s.', json_encode($data)));
        }
    }

    public function visitInteger($data, array $type): int
    {
        return (integer)$data;
    }

    public function visitDouble($data, array $type): float
    {
        return (double)$data;
    }

    public function visitArray($data, array $type): array
    {
        // handle key-value-pairs
        if (null !== $this->currentMetadata && $this->currentMetadata->isXmlKeyValuePairs()) {
            if (2 !== count($type['params'])) {
                throw new RuntimeException('The array type must be specified as "array<K,V>" for Key-Value-Pairs.');
            }
            $this->revertCurrentMetadata();

            list($keyType, $entryType) = $type['params'];

            $result = [];
            foreach ($data as $key => $v) {
                $k = $this->navigator->accept($key, $keyType);
                $result[$k] = $this->navigator->accept($v, $entryType);
            }

            return $result;
        }

        $entryName = null !== $this->currentMetadata && $this->currentMetadata->getXmlEntryName() ? $this->currentMetadata->getXmlEntryName() : 'entry';
        $namespace = null !== $this->currentMetadata && $this->currentMetadata->getXmlEntryNamespace() ? $this->currentMetadata->getXmlEntryNamespace() : null;

        if ($namespace === null && $this->objectMetadataStack->count()) {
            $classMetadata = $this->objectMetadataStack->top();
            $namespace = $classMetadata->getXmlNamespaces()[''] ?? $namespace;
            if ($namespace === null) {
                $namespaces = $data->getDocNamespaces();
                if (isset($namespaces[''])) {
                    $namespace = $namespaces[''];
                }
            }
        }

        if (null !== $namespace) {
            $prefix = uniqid('ns-');
            $data->registerXPathNamespace($prefix, $namespace);
            $nodes = $data->xpath("$prefix:$entryName");
        } else {
            $nodes = $data->xpath($entryName);
        }

        if (!\count($nodes)) {
            return [];
        }

        switch (\count($type['params'])) {
            case 0:
                throw new RuntimeException(sprintf('The array type must be specified either as "array<T>", or "array<K,V>".'));

            case 1:
                $result = [];

                foreach ($nodes as $v) {
                    $result[] = $this->navigator->accept($v, $type['params'][0]);
                }

                return $result;

            case 2:
                if (null === $this->currentMetadata) {
                    throw new RuntimeException('Maps are not supported on top-level without metadata.');
                }

                list($keyType, $entryType) = $type['params'];
                $result = [];

                $nodes = $data->children($namespace)->$entryName;
                foreach ($nodes as $v) {
                    $attrs = $v->attributes();
                    if (!isset($attrs[$this->currentMetadata->getXmlKeyAttribute()])) {
                        throw new RuntimeException(sprintf('The key attribute "%s" must be set for each entry of the map.', $this->currentMetadata->getXmlKeyAttribute()));
                    }

                    $k = $this->navigator->accept($attrs[$this->currentMetadata->getXmlKeyAttribute()], $keyType);
                    $result[$k] = $this->navigator->accept($v, $entryType);
                }

                return $result;

            default:
                throw new LogicException(sprintf('The array type does not support more than 2 parameters, but got %s.', json_encode($type['params'])));
        }
    }

    public function visitDiscriminatorMapProperty($data, ClassMetadataInterface $metadata): string
    {
        switch (true) {
            // Check XML attribute without namespace for discriminatorFieldName
            case $metadata->getsXmlDiscriminatorAttribute() && null === $metadata->getXmlDiscriminatorNamespace() && isset($data->attributes()->{$metadata->getDiscriminatorFieldName()}):
                return (string)$data->attributes()->{$metadata->getDiscriminatorFieldName()};

            // Check XML attribute with namespace for discriminatorFieldName
            case $metadata->getsXmlDiscriminatorAttribute() && null !== $metadata->getXmlDiscriminatorNamespace() && isset($data->attributes($metadata->getXmlDiscriminatorNamespace())->{$metadata->getDiscriminatorFieldName()}):
                return (string)$data->attributes($metadata->getXmlDiscriminatorNamespace())->{$metadata->getDiscriminatorFieldName()};

            // Check XML element with namespace for discriminatorFieldName
            case !$metadata->getsXmlDiscriminatorAttribute() && null !== $metadata->getXmlDiscriminatorNamespace() && isset($data->children($metadata->getXmlDiscriminatorNamespace())->{$metadata->getDiscriminatorFieldName()}):
                return (string)$data->children($metadata->getXmlDiscriminatorNamespace())->{$metadata->getDiscriminatorFieldName()};
            // Check XML element for discriminatorFieldName
            case isset($data->{$metadata->getDiscriminatorFieldName()}):
                return (string)$data->{$metadata->getDiscriminatorFieldName()};

            default:
                throw new LogicException(sprintf(
                    'The discriminator field name "%s" for base-class "%s" was not found in input data.',
                    $metadata->getDiscriminatorFieldName(),
                    $metadata->getName()
                ));
        }
    }

    public function startVisitingObject(ClassMetadataInterface $metadata, object $object, array $type): void
    {
        $this->setCurrentObject($object);
        $this->objectMetadataStack->push($metadata);
    }

    public function visitProperty(PropertyMetadataInterface $metadata, $data)
    {
        $name = $metadata->getSerializedName();

        if (!$metadata->getType()) {
            throw new RuntimeException(sprintf('You must define a type for %s::$%s.', $metadata->reflection->class, $metadata->getName()));
        }

        if ($metadata->isXmlAttribute()) {

            $attributes = $data->attributes($metadata->getXmlNamespace());
            if (isset($attributes[$name])) {
                return $this->navigator->accept($attributes[$name], $metadata->getType());
            }

            throw new NotAcceptableException();
        }

        if ($metadata->isXmlValue()) {
            return $this->navigator->accept($data, $metadata->getType());
        }

        if ($metadata->isXmlCollection()) {
            $enclosingElem = $data;
            if (!$metadata->isXmlCollectionInline()) {
                $enclosingElem = $data->children($metadata->getXmlNamespace())->$name;
            }

            $this->setCurrentMetadata($metadata);
            $v = $this->navigator->accept($enclosingElem, $metadata->getType());
            $this->revertCurrentMetadata();
            return $v;
        }

        if ($metadata->getXmlNamespace()) {
            $node = $data->children($metadata->getXmlNamespace())->$name;
            if (!$node->count()) {
                throw new NotAcceptableException();
            }
        } else {

            $namespaces = $data->getDocNamespaces();

            if (isset($namespaces[''])) {
                $prefix = uniqid('ns-');
                $data->registerXPathNamespace($prefix, $namespaces['']);
                $nodes = $data->xpath('./' . $prefix . ':' . $name);
            } else {
                $nodes = $data->xpath('./' . $name);
            }
            if (empty($nodes)) {
                throw new NotAcceptableException();
            }
            $node = reset($nodes);
        }

        if ($metadata->isXmlKeyValuePairs()) {
            $this->setCurrentMetadata($metadata);
        }

        return $this->navigator->accept($node, $metadata->getType());
    }

    /**
     * @param ClassMetadataInterface $metadata
     * @param mixed                  $data
     * @param array                  $type
     *
     * @return mixed
     */
    public function endVisitingObject(ClassMetadataInterface $metadata, $data, array $type): object
    {
        $rs = $this->currentObject;
        $this->objectMetadataStack->pop();
        $this->revertCurrentObject();

        return $rs;
    }

    public function setCurrentObject($object)
    {
        $this->objectStack->push($this->currentObject);
        $this->currentObject = $object;
    }

    public function getCurrentObject()
    {
        return $this->currentObject;
    }

    public function revertCurrentObject()
    {
        return $this->currentObject = $this->objectStack->pop();
    }

    public function setCurrentMetadata(PropertyMetadataInterface $metadata)
    {
        $this->metadataStack->push($this->currentMetadata);
        $this->currentMetadata = $metadata;
    }

    public function getCurrentMetadata()
    {
        return $this->currentMetadata;
    }

    public function revertCurrentMetadata()
    {
        return $this->currentMetadata = $this->metadataStack->pop();
    }

    public function getResult($data)
    {
        return $data;
    }

    /**
     * Retrieves internalSubset even in bugfixed php versions
     *
     * @param string $data
     * @return string
     */
    private function getDomDocumentTypeEntitySubset($data)
    {
        $startPos = $endPos = stripos($data, '<!doctype');
        $braces = 0;
        do {
            $char = $data[$endPos++];
            if ($char === '<') {
                ++$braces;
            }
            if ($char === '>') {
                --$braces;
            }
        } while ($braces > 0);

        $internalSubset = substr($data, $startPos, $endPos - $startPos);
        $internalSubset = str_replace(["\n", "\r"], '', $internalSubset);
        $internalSubset = preg_replace('/\s{2,}/', ' ', $internalSubset);
        $internalSubset = str_replace(["[ <!", "> ]>"], ['[<!', '>]>'], $internalSubset);

        return $internalSubset;
    }

    /**
     * @param mixed $value
     *
     * @return bool
     */
    public function isNull($value): bool
    {
        if ($value instanceof \SimpleXMLElement) {
            // Workaround for https://bugs.php.net/bug.php?id=75168 and https://github.com/schmittjoh/serializer/issues/817
            // If the "name" is empty means that we are on an not-existent node and subsequent operations on the object will trigger the warning:
            // "Node no longer exists"
            if ($value->getName() === "") {
                // @todo should be "true", but for collections needs a default collection value. maybe something for the 2.0
                return false;
            }

            $xsiAttributes = $value->attributes('http://www.w3.org/2001/XMLSchema-instance');
            if (isset($xsiAttributes['nil'])
                && ((string)$xsiAttributes['nil'] === 'true' || (string)$xsiAttributes['nil'] === '1')
            ) {
                return true;
            }
        }

        return $value === null;
    }
}
