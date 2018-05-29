<?php

declare(strict_types=1);

namespace JMS\Serializer\Exclusion;

use JMS\Serializer\Context;
use JMS\Serializer\Metadata\ClassMetadataInterface;
use JMS\Serializer\Metadata\PropertyMetadataInterface;

/**
 * @author Adrien Brault <adrien.brault@gmail.com>
 */
final class DepthExclusionStrategy implements ExclusionStrategyInterface
{
    /**
     * {@inheritDoc}
     */
    public function shouldSkipClass(ClassMetadataInterface $metadata, Context $context): bool
    {
        return $this->isTooDeep($context);
    }

    /**
     * {@inheritDoc}
     */
    public function shouldSkipProperty(PropertyMetadataInterface $property, Context $context): bool
    {
        return $this->isTooDeep($context);
    }

    private function isTooDeep(Context $context): bool
    {
        $depth = $context->getDepth();
        $metadataStack = $context->getMetadataStack();

        $nthProperty = 0;
        // iterate from the first added items to the lasts
        for ($i = $metadataStack->count() - 1; $i > 0; $i--) {
            $metadata = $metadataStack[$i];
            if ($metadata instanceof PropertyMetadataInterface) {
                $nthProperty++;
                $relativeDepth = $depth - $nthProperty;

                if (null !== $metadata->getMaxDepth() && $relativeDepth > $metadata->getMaxDepth()) {
                    return true;
                }
            }
        }

        return false;
    }
}
