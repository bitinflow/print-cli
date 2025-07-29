<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Smalot\Cups\Builder\Builder;
use Smalot\Cups\Manager\PrinterManager as CupsPrinterManager;
use Smalot\Cups\Transport\Client;
use Smalot\Cups\Transport\ResponseParser;

class PrinterManager
{
    protected CupsPrinterManager $printerManager;

    public function __construct()
    {
        $client = new Client();
        $builder = new Builder();
        $responseParser = new ResponseParser();
        $this->printerManager = new CupsPrinterManager($builder, $client, $responseParser);
    }

    public function getList(): Collection
    {
        return Collection::make($this->printerManager->getList());
    }
}
