<?php
namespace Esenliyim\Listimporter;

use DOMDocument;
use Error;
use Esenliyim\Listimporter\Classes\ImportedList;

define("ACCEPTED_IDS", '/(^ur\d+$)|(^ls\d+$)/');

class ListImporter {

    protected $type;
    protected $target;
    protected $importedRaw;
    protected $importedParsed = null;
    protected $fetched = false;

    public function __construct($target)
    {
        preg_match(ACCEPTED_IDS, $target, $match);
        if (count($match) != 0) {
            $this->type = isset($match[2]) ? 'list' : 'user';
        } else {
            throw new Error("Invalid target");
        }
        $this->target = $target;
    }

    public function getParsed(): ImportedList
    {
        if (!$this->importedParsed) {
            $this->importedParsed = new ImportedList($this->getRaw());
        }
        return $this->importedParsed;
    }

    public function getRaw(): array
    {
        if (!$this->fetched) {
            $this->_import();
        }
        return $this->importedRaw;
    }

    public function refetch(): Listimporter
    {
        $this->_import();
        return $this;
    }

    private function _import(): void
    {
        $this->type === 'list' ? $this->_fetchFromListId() : $this->_fetchFromUserId();
        $this->fetched = true;
    }

    private function _fetchFromListId(): void
    {
        $this->importedRaw = $this->_getList($this->target);
    }

    private function _fetchFromUserId(): void
    {
        $listId = $this->_getListIdFromUserId($this->target);
        $this->importedRaw = $this->_getList($listId);
    }

    private function _getListIdFromUserId(string $id): string
    {
        $url = "https://www.imdb.com/user/$id/watchlist";
        $input = fopen($url, 'r');
        if (!$input) {
            throw new Error("could not get list id");
        } else {
            return $this->_extractListIdFromRawHtml($input);
        }
    }

    private function _extractListIdFromRawHtml($raw): string
    {
        $html = new DOMDocument();
        $html->loadHTML(stream_get_contents($raw));
        $metas = $html->getElementsByTagName('meta');
        
        foreach ($metas as $meta)
        {
            if($meta->attributes->item(0)->nodeValue !== 'pageId') {
                continue;
            } else {
                $listId = $meta->attributes->item(1)->textContent;
                return $listId;
            }
        }
        throw new Error("could not get list id");
    }

    private function _getList(string $id): array
    {
        $url = "https://www.imdb.com/list/$id/export";
        $input = fopen($url, 'r');
        if (!$input) {
            throw new Error("Could not fetch list");
        }
        $gotten = stream_get_contents($input);
        $arrayified = $this->_arrayify($gotten);
        return $this->_parseFetchedCsv($arrayified);
    }

    private function _parseFetchedCsv(array $arrayified): array
    { 
        $csv = array_map("str_getcsv", $arrayified);
        $keys = array_shift($csv);

        foreach ($keys as $key => $value) {
            $keys[$key] = strtolower($value);
        }
        foreach ($csv as $i => $row) {
            $csv[$i] = array_combine($keys, $row);
        }
        return $csv;
    }

    private function _arrayify($input): array
    {
        $arrayified = explode("\n", $input);
        while ($arrayified[count($arrayified) - 1] == "") {
            array_pop($arrayified);
        }
        return $arrayified;
    }

    public function getType(): string
    {
        return $this->type;
    }
}