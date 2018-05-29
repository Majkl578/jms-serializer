<?php

declare(strict_types=1);

namespace JMS\Serializer\Metadata;

interface PropertyMetadataInterface
{
    public const ACCESS_TYPE_PROPERTY = 'property';
    public const ACCESS_TYPE_PUBLIC_METHOD = 'public_method';

    public function getClass() : string;

    public function getName() : string;

    public function getSinceVersion() : ?string;

    public function getUntilVersion() : ?string;

    /**
     * @return string[]|null
     */
    public function getGroups() : ?array;

    public function getSerializedName() : ?string;

    /**
     * @return mixed[]|null
     */
    public function getType(): ?array;

    public function isXmlCollection() : bool;

    public function isXmlCollectionInline() : bool;

    public function isXmlCollectionSkippedWhenEmpty() : bool;

    public function getXmlEntryName() : ?string;

    public function getXmlEntryNamespace() : ?string;

    public function getXmlKeyAttribute() : ?string;

    public function isXmlAttribute() : bool;

    public function isXmlValue() : bool;

    public function getXmlNamespace() : ?string;

    public function isXmlKeyValuePairs() : bool;

    public function isXmlElementCData() : bool;

    public function getGetter() : ?string;

    public function getSetter() : ?string;

    public function isInline() : bool;

    public function isSkippedWhenEmpty() : bool;

    public function isReadOnly() : bool;

    public function isXmlAttributeMap() : bool;

    public function getMaxDepth() : ?int;

    public function getExcludeIf() : ?string;
}
