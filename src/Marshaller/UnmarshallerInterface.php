<?php

declare(strict_types=1);

namespace JMS\Serializer\Marshaller;

interface UnmarshallerInterface
{
    public function __invoke(string $data) : object;
}
