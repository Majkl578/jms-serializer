<?php

declare(strict_types=1);

namespace JMS\Serializer;

use JMS\Serializer\Exception\NotAcceptableException;
use JMS\Serializer\Exception\RuntimeException;
use JMS\Serializer\Metadata\ClassMetadataInterface;
use JMS\Serializer\Metadata\PropertyMetadataInterface;
use JMS\Serializer\Visitor\SerializationVisitorInterface;

/**
 * XmlSerializationVisitor.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
final class XmlSerializationVisitor extends AbstractVisitor implements SerializationVisitorInterface
{
    /**
     * @var \DOMDocument
     */
    private $document;

    private $defaultRootName = 'result';
    private $defaultRootNamespace;
    private $defaultRootPrefix;
    private $stack;
    private $metadataStack;
    private $currentNode;

    /** @var PropertyMetadataInterface */
    private $currentMetadata;
    private $hasValue;
    private $nullWasVisited;
    private $objectMetadataStack;

    public function __construct(
        bool $formatOutput = true,
        string $defaultEncoding = 'UTF-8',
        string $defaultVersion = '1.0',
        string $defaultRootName = 'result',
        string $defaultRootNamespace = null,
        string $defaultRootPrefix = null
    ) {
        $this->objectMetadataStack = new \SplStack;
        $this->stack = new \SplStack;
        $this->metadataStack = new \SplStack;

        $this->currentNode = null;
        $this->nullWasVisited = false;

        $this->document = $this->createDocument($formatOutput, $defaultVersion, $defaultEncoding);

        $this->defaultRootName = $defaultRootName;
        $this->defaultRootNamespace = $defaultRootNamespace;
        $this->defaultRootPrefix = $defaultRootPrefix;
    }

    private function createDocument(bool $formatOutput, string $defaultVersion, string $defaultEncoding): \DOMDocument
    {
        $document = new \DOMDocument($defaultVersion, $defaultEncoding);
        $document->formatOutput = $formatOutput;

        return $document;
    }

    public function createRoot(ClassMetadataInterface $metadata = null, ?string $rootName = null, ?string $rootNamespace = null, ?string $rootPrefix = null)
    {
        if ($metadata !== null && !empty($metadata->getXmlRootName())) {
            $rootPrefix = $metadata->getXmlRootPrefix();
            $rootName = $metadata->getXmlRootName();
            $rootNamespace = $metadata->getXmlRootNamespace() ?: $this->getClassDefaultNamespace($metadata);
        } else {
            $rootName = $rootName ?: ($this->defaultRootName);
            $rootNamespace = $rootNamespace ?: $this->defaultRootNamespace;
            $rootPrefix = $rootPrefix ?: $this->defaultRootPrefix;
        }

        $document = $this->getDocument();
        if ($rootNamespace) {
            $rootNode = $document->createElementNS($rootNamespace, ($rootPrefix !== null ? ($rootPrefix . ':') : '') . $rootName);
        } else {
            $rootNode = $document->createElement($rootName);
        }
        $document->appendChild($rootNode);
        $this->setCurrentNode($rootNode);

        return $rootNode;
    }

    public function visitNull($data, array $type)
    {
        $node = $this->document->createAttribute('xsi:nil');
        $node->value = 'true';
        $this->nullWasVisited = true;

        return $node;
    }

    public function visitString(string $data, array $type)
    {
        $doCData = null !== $this->currentMetadata ? $this->currentMetadata->isXmlElementCData() : true;

        return $doCData ? $this->document->createCDATASection($data) : $this->document->createTextNode((string)$data);
    }

    public function visitSimpleString($data, array $type)
    {
        return $this->document->createTextNode((string)$data);
    }

    public function visitBoolean(bool $data, array $type)
    {
        return $this->document->createTextNode($data ? 'true' : 'false');
    }

    public function visitInteger(int $data, array $type)
    {
        return $this->document->createTextNode((string)$data);
    }

    public function visitDouble(float $data, array $type)
    {
        if (floor($data) === $data) {
            return $this->document->createTextNode($data . ".0");
        } else {
            return $this->document->createTextNode((string)$data);
        }
    }

    public function visitArray(array $data, array $type)
    {
        if ($this->currentNode === null) {
            $this->createRoot();
        }

        $entryName = (null !== $this->currentMetadata && null !== $this->currentMetadata->getXmlEntryName()) ? $this->currentMetadata->getXmlEntryName() : 'entry';
        $keyAttributeName = (null !== $this->currentMetadata && null !== $this->currentMetadata->getXmlKeyAttribute()) ? $this->currentMetadata->getXmlKeyAttribute() : null;
        $namespace = (null !== $this->currentMetadata && null !== $this->currentMetadata->getXmlEntryNamespace()) ? $this->currentMetadata->getXmlEntryNamespace() : null;

        $elType = $this->getElementType($type);
        foreach ($data as $k => $v) {

            $tagName = (null !== $this->currentMetadata && $this->currentMetadata->isXmlKeyValuePairs() && $this->isElementNameValid((string)$k)) ? $k : $entryName;

            $entryNode = $this->createElement($tagName, $namespace);
            $this->currentNode->appendChild($entryNode);
            $this->setCurrentNode($entryNode);

            if (null !== $keyAttributeName) {
                $entryNode->setAttribute($keyAttributeName, (string)$k);
            }

            try {
                if (null !== $node = $this->navigator->accept($v, $elType)) {
                    $this->currentNode->appendChild($node);
                }
            } catch (NotAcceptableException $e) {
                $this->currentNode->parentNode->removeChild($this->currentNode);
            }

            $this->revertCurrentNode();
        }
    }

    public function startVisitingObject(ClassMetadataInterface $metadata, object $data, array $type): void
    {
        $this->objectMetadataStack->push($metadata);

        if ($this->currentNode === null) {
            $this->createRoot($metadata);
        }

        $this->addNamespaceAttributes($metadata, $this->currentNode);

        $this->hasValue = false;
    }

    public function visitProperty(PropertyMetadataInterface $metadata, $v): void
    {
        if ($metadata->isXmlAttribute()) {
            $this->setCurrentMetadata($metadata);
            $node = $this->navigator->accept($v, $metadata->getType());
            $this->revertCurrentMetadata();

            if (!$node instanceof \DOMCharacterData) {
                throw new RuntimeException(sprintf('Unsupported value for XML attribute for %s. Expected character data, but got %s.', $metadata->getName(), json_encode($v)));
            }

            $this->setAttributeOnNode($this->currentNode, $metadata->getSerializedName(), $node->nodeValue, $metadata->getXmlNamespace());

            return;
        }

        if (($metadata->isXmlValue() && $this->currentNode->childNodes->length > 0)
            || (!$metadata->isXmlValue() && $this->hasValue)
        ) {
            throw new RuntimeException(sprintf('If you make use of @XmlValue, all other properties in the class must have the @XmlAttribute annotation. Invalid usage detected in class %s.', $metadata->getClass()));
        }

        if ($metadata->isXmlValue()) {
            $this->hasValue = true;

            $this->setCurrentMetadata($metadata);
            $node = $this->navigator->accept($v, $metadata->getType());
            $this->revertCurrentMetadata();

            if (!$node instanceof \DOMCharacterData) {
                throw new RuntimeException(sprintf('Unsupported value for property %s::$%s. Expected character data, but got %s.', $metadata->reflection->class, $metadata->reflection->name, \is_object($node) ? \get_class($node) : \gettype($node)));
            }

            $this->currentNode->appendChild($node);

            return;
        }

        if ($metadata->isXmlAttributeMap()) {
            if (!\is_array($v)) {
                throw new RuntimeException(sprintf('Unsupported value type for XML attribute map. Expected array but got %s.', \gettype($v)));
            }

            foreach ($v as $key => $value) {
                $this->setCurrentMetadata($metadata);
                $node = $this->navigator->accept($value, null);
                $this->revertCurrentMetadata();

                if (!$node instanceof \DOMCharacterData) {
                    throw new RuntimeException(sprintf('Unsupported value for a XML attribute map value. Expected character data, but got %s.', json_encode($v)));
                }

                $this->setAttributeOnNode($this->currentNode, $key, $node->nodeValue, $metadata->getXmlNamespace());
            }

            return;
        }

        if ($addEnclosingElement = !$this->isInLineCollection($metadata) && !$metadata->isInline()) {

            $namespace = null !== $metadata->getXmlNamespace()
                ? $metadata->getXmlNamespace()
                : $this->getClassDefaultNamespace($this->objectMetadataStack->top());

            $element = $this->createElement($metadata->getSerializedName(), $namespace);
            $this->currentNode->appendChild($element);
            $this->setCurrentNode($element);
        }

        $this->setCurrentMetadata($metadata);

        try {
            if (null !== $node = $this->navigator->accept($v, $metadata->getType())) {
                $this->currentNode->appendChild($node);
            }
        } catch (NotAcceptableException $e) {
            $this->currentNode->parentNode->removeChild($this->currentNode);
            $this->revertCurrentMetadata();
            $this->revertCurrentNode();
            $this->hasValue = false;
            return;
        }

        $this->revertCurrentMetadata();

        if ($addEnclosingElement) {
            $this->revertCurrentNode();

            if ($this->isElementEmpty($element) && ($v === null || $this->isSkippableCollection($metadata) || $this->isSkippableEmptyObject($node, $metadata))) {
                $this->currentNode->removeChild($element);
            }
        }

        $this->hasValue = false;
    }

    private function isInLineCollection(PropertyMetadataInterface $metadata)
    {
        return $metadata->isXmlCollection() && $metadata->isXmlCollectionInline();
    }

    private function isSkippableEmptyObject($node, PropertyMetadataInterface $metadata)
    {
        return $node === null && !$metadata->isXmlCollection() && $metadata->isSkippedWhenEmpty();
    }

    private function isSkippableCollection(PropertyMetadataInterface $metadata)
    {
        return $metadata->isXmlCollection() && $metadata->isXmlCollectionSkippedWhenEmpty();
    }

    private function isElementEmpty(\DOMElement $element)
    {
        return !$element->hasChildNodes() && !$element->hasAttributes();
    }

    public function endVisitingObject(ClassMetadataInterface $metadata, object $data, array $type)
    {
        $this->objectMetadataStack->pop();
    }

    public function getResult($node)
    {
        if ($this->document->documentElement === null) {
            if ($node instanceof \DOMElement) {
                $this->document->appendChild($node);
            } else {
                $this->createRoot();
                if ($node) {
                    $this->document->documentElement->appendChild($node);
                }
            }
        }

        if ($this->nullWasVisited) {
            $this->document->documentElement->setAttributeNS(
                'http://www.w3.org/2000/xmlns/',
                'xmlns:xsi',
                'http://www.w3.org/2001/XMLSchema-instance'
            );
        }
        return $this->document->saveXML();
    }

    public function getCurrentNode(): ?\DOMNode
    {
        return $this->currentNode;
    }

    public function getCurrentMetadata(): ?PropertyMetadataInterface
    {
        return $this->currentMetadata;
    }

    public function getDocument(): \DOMDocument
    {
        if (null === $this->document) {
            $this->document = $this->createDocument();
        }
        return $this->document;
    }

    public function setCurrentMetadata(PropertyMetadataInterface $metadata): void
    {
        $this->metadataStack->push($this->currentMetadata);
        $this->currentMetadata = $metadata;
    }

    public function setCurrentNode(\DOMNode $node): void
    {
        $this->stack->push($this->currentNode);
        $this->currentNode = $node;
    }

    public function setCurrentAndRootNode(\DOMNode $node): void
    {
        $this->setCurrentNode($node);
        $this->document->appendChild($node);
    }

    public function revertCurrentNode(): ?\DOMNode
    {
        return $this->currentNode = $this->stack->pop();
    }

    public function revertCurrentMetadata(): ?PropertyMetadataInterface
    {
        return $this->currentMetadata = $this->metadataStack->pop();
    }

    public function prepare($data)
    {
        $this->nullWasVisited = false;

        return $data;
    }

    /**
     * Checks that the name is a valid XML element name.
     *
     * @param string $name
     *
     * @return boolean
     */
    private function isElementNameValid($name)
    {
        return $name && false === strpos($name, ' ') && preg_match('#^[\pL_][\pL0-9._-]*$#ui', $name);
    }

    /**
     * Adds namespace attributes to the XML root element
     *
     * @param ClassMetadataInterface $metadata
     * @param \DOMElement            $element
     */
    private function addNamespaceAttributes(ClassMetadataInterface $metadata, \DOMElement $element)
    {
        foreach ($metadata->getXmlNamespaces() as $prefix => $uri) {
            $attribute = 'xmlns';
            if ($prefix !== '') {
                $attribute .= ':' . $prefix;
            } elseif ($element->namespaceURI === $uri) {
                continue;
            }
            $element->setAttributeNS('http://www.w3.org/2000/xmlns/', $attribute, $uri);
        }
    }

    private function createElement($tagName, $namespace = null)
    {
        if (null === $namespace) {
            return $this->document->createElement($tagName);
        }
        if ($this->currentNode->isDefaultNamespace($namespace)) {
            return $this->document->createElementNS($namespace, $tagName);
        }
        if (!($prefix = $this->currentNode->lookupPrefix($namespace)) && !($prefix = $this->document->lookupPrefix($namespace))) {
            $prefix = 'ns-' . substr(sha1($namespace), 0, 8);
        }
        return $this->document->createElementNS($namespace, $prefix . ':' . $tagName);
    }

    private function setAttributeOnNode(\DOMElement $node, $name, $value, $namespace = null)
    {
        if (null !== $namespace) {
            if (!$prefix = $node->lookupPrefix($namespace)) {
                $prefix = 'ns-' . substr(sha1($namespace), 0, 8);
            }
            $node->setAttributeNS($namespace, $prefix . ':' . $name, $value);
        } else {
            $node->setAttribute($name, $value);
        }
    }

    private function getClassDefaultNamespace(ClassMetadataInterface $metadata)
    {
        return $metadata->getXmlNamespaces()[''] ?? null;
    }
}
