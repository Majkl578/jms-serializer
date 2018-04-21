<?php

declare(strict_types=1);

namespace JMS\Serializer\Marshaller\Generator\Exporter;

use PhpParser\Node;
use PhpParser\PrettyPrinterAbstract;
use function file_put_contents;

final class FileExporter implements ExporterInterface
{
    /** @var PrettyPrinterAbstract */
    private $printer;

    public function __construct(PrettyPrinterAbstract $printer)
    {
        $this->printer = $printer;
    }

    public function export(Node $node) : void
    {
        file_put_contents('codegen.php', $this->printer->prettyPrintFile([$node]));
    }
}
