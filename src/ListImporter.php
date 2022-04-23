<?php
namespace Esenliyim\Listimporter;

include('./src/classes/Film.php');
include('./src/classes/ImportedList.php');

use DateTime;
use DOMDocument;
use Error;
use Esenliyim\Listimporter\Classes\Film;
use Esenliyim\Listimporter\Classes\ImportedList;

define("ACCEPTED_IDS", '/(^ur\d+$)|(^ls\d+$)/');

class Listimporter {

    protected $type;
    protected $target;

    public function __construct($target, $options = null)
    {
        preg_match(ACCEPTED_IDS, $target, $match);
        if (count($match) != 0) {
            $this->type = isset($match[2]) ? 'list' : 'user';
        } else {
            throw new Error("Invalid target");
        }
        $this->target = $target;
    }

    public function fetchList(): ImportedList
    {
        return new ImportedList($this->type === 'list' ? $this->_fetchFromListId() : $this->_fetchFromUserId());
    }

    private function _fetchFromListId()
    {
        return $this->_getList($this->target);
    }

    private function _fetchFromUserId()
    {
        $listId = $this->_getListIdFromUserId($this->target);
        return $this->_getList($listId);
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

    private function _getList(string $id)
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

error_reporting(1);

$args = getopt("l:");

$bok = new Listimporter($args['l']);
$annen = $bok->fetchList();

echo count($annen->filterByGenres(["Action", "Drama"], false));