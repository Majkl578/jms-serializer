<?php

declare(strict_types=1);

namespace JMS\Serializer\Metadata;

interface ExpressionPropertyMetadataInterface extends PropertyMetadataInterface
{
    public function getExpression() : string;
}
