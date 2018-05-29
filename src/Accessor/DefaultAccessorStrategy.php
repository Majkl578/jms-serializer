<?php

declare(strict_types=1);

namespace JMS\Serializer\Accessor;

use JMS\Serializer\Exception\ExpressionLanguageRequiredException;
use JMS\Serializer\Exception\LogicException;
use JMS\Serializer\Expression\ExpressionEvaluatorInterface;
use JMS\Serializer\Metadata\ExpressionPropertyMetadataInterface;
use JMS\Serializer\Metadata\PropertyMetadataInterface;
use JMS\Serializer\Metadata\StaticPropertyMetadataInterface;

/**
 * @author Asmir Mustafic <goetas@gmail.com>
 */
final class DefaultAccessorStrategy implements AccessorStrategyInterface
{
    private $readAccessors = array();
    private $writeAccessors = array();

    /**
     * @var ExpressionEvaluatorInterface
     */
    private $evaluator;

    public function __construct(ExpressionEvaluatorInterface $evaluator = null)
    {
        $this->evaluator = $evaluator;
    }

    public function getValue(object $object, PropertyMetadataInterface $metadata)
    {
        if ($metadata instanceof StaticPropertyMetadataInterface) {
            return $metadata->getValue();
        }

        if ($metadata instanceof ExpressionPropertyMetadataInterface) {
            if ($this->evaluator === null) {
                throw new ExpressionLanguageRequiredException(sprintf('The property %s on %s requires the expression accessor strategy to be enabled.', $metadata->getName(), $metadata->getClass()));
            }

            return $this->evaluator->evaluate($metadata->getExpression(), ['object' => $object]);
        }

        if (null === $metadata->getGetter()) {
            if (!isset($this->readAccessors[$metadata->getClass()])) {
                $this->readAccessors[$metadata->getClass()] = \Closure::bind(function ($o, $name) {
                    return $o->$name;
                }, null, $metadata->getClass());
            }

            return $this->readAccessors[$metadata->getClass()]($object, $metadata->getName());
        }

        return $object->{$metadata->getGetter()}();
    }

    public function setValue(object $object, $value, PropertyMetadataInterface $metadata): void
    {
        if ($metadata->isReadOnly()) {
            throw new LogicException(sprintf('%s on %s is read only.'), $metadata->getName(), $metadata->getClass());
        }

        if (null === $metadata->getSetter()) {
            if (!isset($this->writeAccessors[$metadata->getClass()])) {
                $this->writeAccessors[$metadata->getClass()] = \Closure::bind(function ($o, $name, $value) {
                    $o->$name = $value;
                }, null, $metadata->getClass());
            }

            $this->writeAccessors[$metadata->getClass()]($object, $metadata->getName(), $value);
            return;
        }

        $object->{$metadata->getSetter()}($value);
    }
}
