<?php

use Esenliyim\Listimporter\Classes\Client;
use Esenliyim\Listimporter\Exceptions\InvalidImdbIdException;
use Esenliyim\Listimporter\Exceptions\PrivateListException;
use Esenliyim\Listimporter\ListImporter;
use PHPUnit\Framework\TestCase;

class ListImporterTest extends TestCase {
    const PRIVATE_WATCHLIST_PATH = "/data/samplePrivateWatchlist.html";
    const PUBLIC_WATCHLIST_PATH = "/data/samplePublicWatchlist.html";
    const SAMPLE_CSV_PATH = "/data/imdbwlist.csv";

    public function setUp(): void {

    }

    public function testDetectsPrivateWatchlist() {
        $clientMock = $this->createMock(Client::class);
        $samplePrivateWatchlist = fopen(dirname(__FILE__) . self::PRIVATE_WATCHLIST_PATH, 'r');
        $clientMock->method('fetchWithUserId')->willReturn($samplePrivateWatchlist);

        $id = "ur1";
        $importer = new ListImporter($id, $clientMock);
        $this->expectException(PrivateListException::class);
        $importer->getRaw();
    }

    public function testFindsListId()
    {
        $clientMock = $this->createMock(Client::class);
        $samplePublicWatchlist = fopen(dirname(__FILE__) . self::PUBLIC_WATCHLIST_PATH, 'r');
        $sampleCSV = fopen(dirname(__FILE__) . self::SAMPLE_CSV_PATH, 'r');
        $clientMock->method('fetchWithUserId')->willReturn($samplePublicWatchlist);
        $clientMock->method('fetchWithListId')->willReturn($sampleCSV);

        $id = "ur1";
        $importer = new ListImporter($id, $clientMock);
        try {
            $importer->getRaw();
            $this->addToAssertionCount(1);
        } catch (Throwable $t) {
            $this->assertNotEquals("could not find listId", $t->getMessage());
        }
    }

    public function testRejectsInvalidIds()
    {
        $invalids = ["asd", "", "123123", "ur", "ls", "als12123", "2ls012123", "ls955959a", "sur232442", "lsur152525", "ls 12424"];
        foreach($invalids as $invalid)
        {
            $this->expectException(InvalidImdbIdException::class);
            $importer = new ListImporter($invalid);
        }
    }

    public function testAcceptsValidIds()
    {
        $valids = ["ls123", "ur123213", "ls123 ", "ur5125 ", " ls125", " ur626"];
        foreach($valids as $valid)
        {
            try {
                $importer = new ListImporter($valid);
                $this->addToAssertionCount(1);
            } catch (Throwable $t) {
                $this->fail($t->getMessage());
            }
        }
    }
}
