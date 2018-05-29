<?php

declare(strict_types=1);

namespace JMS\Serializer\Metadata\Driver;

use Doctrine\Common\Persistence\Mapping\ClassMetadata as DoctrineClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadataInterface;

/**
 * This class decorates any other driver. If the inner driver does not provide a
 * a property type, the decorator will guess based on Doctrine 2 metadata.
 */
class DoctrinePHPCRTypeDriver extends AbstractDoctrineTypeDriver
{
    /**
     * @param DoctrineClassMetadata $doctrineMetadata
     * @param PropertyMetadataInterface $propertyMetadata
     */
    protected function hideProperty(DoctrineClassMetadata $doctrineMetadata, PropertyMetadataInterface $propertyMetadata):bool
    {
        return 'lazyPropertiesDefaults' === $propertyMetadata->name
            || $doctrineMetadata->parentMapping === $propertyMetadata->name
            || $doctrineMetadata->node === $propertyMetadata->name;
    }

    protected function setPropertyType(DoctrineClassMetadata $doctrineMetadata, PropertyMetadataInterface $propertyMetadata):void
    {
        $propertyName = $propertyMetadata->getName();
        if ($doctrineMetadata->hasField($propertyName) && $fieldType = $this->normalizeFieldType($doctrineMetadata->getTypeOfField($propertyName))) {
            $field = $doctrineMetadata->getFieldMapping($propertyName);
            if (!empty($field['multivalue'])) {
                $fieldType = 'array';
            }

            $propertyMetadata->setType($this->typeParser->parse($fieldType));
        } elseif ($doctrineMetadata->hasAssociation($propertyName)) {
            try {
                $targetEntity = $doctrineMetadata->getAssociationTargetClass($propertyName);
            } catch (\Exception $e) {
                return;
            }

            if (null === $this->tryLoadingDoctrineMetadata($targetEntity)) {
                return;
            }

            if (!$doctrineMetadata->isSingleValuedAssociation($propertyName)) {
                $targetEntity = "ArrayCollection<{$targetEntity}>";
            }

            $propertyMetadata->setType($this->typeParser->parse($targetEntity));
        }
    }
}
