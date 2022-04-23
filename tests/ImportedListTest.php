<?php

use Esenliyim\Listimporter\Classes\ImportedList;

class ImportedListTest extends \PHPUnit\Framework\TestCase {

    const SERIALIZED_SAMPLE_PATH = "/data/serializedSample";

    protected $imported;

    public function setUp(): void {
        $this->_initImported();
    }

    public function testShortsOnly() {
        $gotten = $this->imported->shortsOnly();
        foreach ($gotten as $entry) {
            $this->assertEquals('short', $entry->titleType);
        }
    }

    public function testReleaseOnly() {
        $today = date("Y-m-d");
        $released = $this->imported->releasedOnly();
        $upcoming = $this->imported->upcomingOnly();
        foreach ($released as $entry) {
            $this->assertLessThanOrEqual($today, $entry->releaseDate);
        }
        foreach ($upcoming as $entry) {
            $this->assertGreaterThan($today, $entry->releaseDate);
        }
    }

    public function testShouldOnlyReturnReleasedWarFilms() {
        $filmTypes = ['tvMovie', 'movie', 'short', 'video'];
        $releasedWarFilms = $this->imported
            ->setReleaseFiter(true)
            ->setGenreFilter(["War"])
            ->setTypeFilter($filmTypes)
            ->getFiltered();
        $today = date("Y-m-d");
        foreach ($releasedWarFilms as $film) {
            $this->assertLessThan($today, $film->releaseDate);
            $this->assertContains("War", $film->genres);
            $this->assertContains($film->titleType, $filmTypes);
        }
    }

    public function testUnsetFiltersShouldReturnEverything() {
        $withoutFilters = $this->imported
            ->getFiltered();
        $this->assertCount(117, $withoutFilters);
    }

    public function testSettingOnlyReleasedFilter() {
        $filterReleasedOnly = $this->imported
            ->setReleaseFiter(false)
            ->getFiltered();
        $this->assertCount(3, $filterReleasedOnly);
    }

    public function testSettingOnlyTypeFilter() {
        $filterTypeOnly = $this->imported
            ->setTypeFilter(["short"])
            ->getFiltered();
        foreach ($filterTypeOnly as $entry) {
            $this->assertEquals("short", $entry->titleType);
        }
    }

    public function testSettingOnlyGenreFilter() {
        $filterGenreOnly = $this->imported
            ->setGenreFilter(["War"])
            ->getFiltered();
        foreach ($filterGenreOnly as $entry) {
            $this->assertContains("War", $entry->genres);
        }
    }

    private function _initImported() {
        $loadedSample = file_get_contents(dirname(__FILE__) . self::SERIALIZED_SAMPLE_PATH);
        $sampleArray = unserialize($loadedSample);
        $this->imported = new ImportedList($sampleArray);
    }
}
