<?php

use Esenliyim\Listimporter\Classes\Client;
use Esenliyim\Listimporter\Exceptions\PrivateListException;
use Esenliyim\Listimporter\ListImporter;
use PHPUnit\Framework\TestCase;

class ListImporterTest extends TestCase {
    const PRIVATE_WATCHLIST_PATH = "/data/samplePrivateWatchlist.html";

    public function setUp(): void {
    }

    public function testPrivateWatchlist() {
        $clientMock = $this->createMock(Client::class);
        $samplePrivateWatchlist = fopen(dirname(__FILE__) . self::PRIVATE_WATCHLIST_PATH, 'r');
        echo ($samplePrivateWatchlist);
        $clientMock->method('fetchWithUserId')->willReturn($samplePrivateWatchlist);

        $id = "ur1";
        $importer = new ListImporter($id, $clientMock);
        $this->expectException(PrivateListException::class);
        $importer->getRaw();
    }
}
