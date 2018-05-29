<?php

declare(strict_types=1);

namespace JMS\Serializer\Accessor;

use JMS\Serializer\Metadata\PropertyMetadataInterface;

/**
 * @author Asmir Mustafic <goetas@gmail.com>
 */
interface AccessorStrategyInterface
{
    /**
     * @param object $object
     * @param PropertyMetadataInterface $metadata
     * @return mixed
     */
    public function getValue(object $object, PropertyMetadataInterface $metadata);

    /**
     * @param object $object
     * @param mixed $value
     * @param PropertyMetadataInterface $metadata
     * @return void
     */
    public function setValue(object $object, $value, PropertyMetadataInterface $metadata): void;
}
