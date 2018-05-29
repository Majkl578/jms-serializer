<?php

declare(strict_types=1);

namespace JMS\Serializer\Metadata;

interface StaticPropertyMetadataInterface extends PropertyMetadataInterface
{
    /**
     * @return mixed
     */
    public function getValue();
}
