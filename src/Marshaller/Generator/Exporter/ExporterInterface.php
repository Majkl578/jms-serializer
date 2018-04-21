<?php

declare(strict_types=1);

namespace JMS\Serializer\Marshaller\Generator\Exporter;

use PhpParser\Node;

interface ExporterInterface
{
    public function export(Node $node) : void;
}
