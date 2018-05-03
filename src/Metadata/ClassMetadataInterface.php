<?php

declare(strict_types=1);

namespace JMS\Serializer\Metadata;

use Metadata\MergeableInterface;

/**
 * Class Metadata used to customize the serialization process.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
interface ClassMetadataInterface extends MergeableInterface
{
    public function getName() : string;

    /**
     * @return PropertyMetadata[]
     */
    public function getProperties() : array;

    public function getAccessorOrder() : string;

    /**
     * @return string[]|null
     */
    public function getCustomOrder() : ?array;

    public function getUsingExpression() : bool;

    public function isList() : bool;

    public function isMap() : bool;

    public function gesDiscriminatorDisabled() : bool;

    public function getDiscriminatorBaseClass() : ?string;

    public function getDiscriminatorFieldName() : ?string;

    public function getDiscriminatorValue() : ?string;

    /**
     * @return string[]
     */
    public function getDiscriminatorMap() : array;

    /**
     * @return string[]
     */
    public function getDiscriminatorGroups() : array;

    public function getXmlRootName() : ?string;

    public function getXmlRootNamespace() : ?string;

    public function getXmlRootPrefix() : ?string;

    /**
     * @return string[]
     */
    public function getXmlNamespaces() : array;

    public function getsXmlDiscriminatorAttribute() : bool;

    public function getXmlDiscriminatorCData() : bool;

    public function getXmlDiscriminatorNamespace() : ?string;

    /**
     * @return \ReflectionMethod[]
     */
    public function getPreSerializeMethods() : array;

    /**
     * @return \ReflectionMethod[]
     */
    public function getPostSerializeMethods() : array;

    /**
     * @return \ReflectionMethod[]
     */
    public function getPostDeserializeMethods() : array;
}
