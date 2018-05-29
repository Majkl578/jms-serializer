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
interface SerializationVisitorInterface extends VisitorInterface
{
    /**
     * @param mixed $data
     * @param array $type
     *
     * @return mixed
     */
    public function visitNull($data, array $type);

    /**
     * @param mixed $data
     * @param array $type
     *
     * @return mixed
     */
    public function visitString(string $data, array $type);

    /**
     * @param mixed $data
     * @param array $type
     *
     * @return mixed
     */
    public function visitBoolean(bool $data, array $type);

    /**
     * @param mixed $data
     * @param array $type
     *
     * @return mixed
     */
    public function visitDouble(float $data, array $type);

    /**
     * @param mixed $data
     * @param array $type
     *
     * @return mixed
     */
    public function visitInteger(int $data, array $type);

    /**
     * @param mixed $data
     * @param array $type
     *
     * @return mixed
     */
    public function visitArray(array $data, array $type);

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
     * @return void
     */
    public function visitProperty(PropertyMetadataInterface $metadata, $data): void;

    /**
     * Called after all properties of the object have been visited.
     *
     * @param ClassMetadataInterface $metadata
     * @param mixed                  $data
     * @param array                  $type
     *
     * @return mixed
     */
    public function endVisitingObject(ClassMetadataInterface $metadata, object $data, array $type);
}
