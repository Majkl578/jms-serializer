<?php

declare(strict_types=1);

namespace JMS\Serializer\Visitor;

use JMS\Serializer\Metadata\ClassMetadataInterface;
use JMS\Serializer\Metadata\PropertyMetadataInterface;
use JMS\Serializer\VisitorInterface;

/**
 * Interface for visitors.
 *
 * This contains the minimal set of values that must be supported for any
 * output format.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 * @author Asmir Mustafic <goetas@gmail.com>
 */
interface DeserializationVisitorInterface extends VisitorInterface
{
    /**
     * @param mixed $data
     * @param array $type
     *
     * @return mixed
     */
    public function visitNull($data, array $type): void;

    /**
     * @param mixed $data
     * @param array $type
     *
     * @return mixed
     */
    public function visitString($data, array $type): string;

    /**
     * @param mixed $data
     * @param array $type
     *
     * @return mixed
     */
    public function visitBoolean($data, array $type): bool;

    /**
     * @param mixed $data
     * @param array $type
     *
     * @return mixed
     */
    public function visitDouble($data, array $type): float;

    /**
     * @param mixed $data
     * @param array $type
     *
     * @return mixed
     */
    public function visitInteger($data, array $type): int;

    /**
     * Returns the class name based on the type of the discriminator map value
     *
     * @param                        $data
     * @param ClassMetadataInterface $metadata
     *
     * @return string
     */
    public function visitDiscriminatorMapProperty($data, ClassMetadataInterface $metadata): string;

    /**
     * @param mixed $data
     * @param array $type
     *
     * @return mixed
     */
    public function visitArray($data, array $type): array;

    /**
     * Called before the properties of the object are being visited.
     *
     * @param ClassMetadataInterface $metadata
     * @param mixed                  $data
     * @param array                  $type
     *
     * @return void
     */
    public function startVisitingObject(ClassMetadataInterface $metadata, object $data, array $type): void;

    /**
     * @param PropertyMetadataInterface $metadata
     * @param mixed $data
     *
     * @return mixed
     */
    public function visitProperty(PropertyMetadataInterface $metadata, $data);

    /**
     * Called after all properties of the object have been visited.
     *
     * @param ClassMetadataInterface $metadata
     * @param mixed                  $data
     * @param array                  $type
     *
     * @return mixed
     */
    public function endVisitingObject(ClassMetadataInterface $metadata, $data, array $type): object;

    /**
     * @param mixed $data
     *
     * @return mixed
     */
    public function getResult($data);
}
