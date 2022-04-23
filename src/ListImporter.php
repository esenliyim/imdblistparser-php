<?php
namespace Esenliyim\Listimporter;

use DOMDocument;
use Esenliyim\Listimporter\Classes\Client;
use Esenliyim\Listimporter\Classes\ImportedList;
use Esenliyim\Listimporter\Exceptions\ImdbRequestException;
use Esenliyim\Listimporter\Exceptions\InvalidImdbIdException;
use Esenliyim\Listimporter\Exceptions\PrivateListException;

class ListImporter {

    const ACCEPTED_IDS =  '/(^ur\d+$)|(^ls\d+$)/';

    protected $type;
    protected $target;
    protected $importedRaw;
    protected $importedParsed = null;
    protected $fetched = false;

    public function __construct($target, Client $client = null)
    {
        preg_match(static::ACCEPTED_IDS, $target, $match);
        if (count($match) != 0) {
            $this->type = isset($match[2]) ? 'list' : 'user';
        } else {
            throw new InvalidImdbIdException("input ID is not a valid user or list ID");
        }
        $this->target = $target;
        $this->client = $client ?: new Client();
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
        $input = $this->client->fetchWithUserId($id);
        if (!$input) {
            throw new ImdbRequestException("could not get list id");
        } else {
            return $this->_extractListIdFromRawHtml($input);
        }
    }

    private function _checkIfPrivate(DOMDocument $html): bool
    {
        return (bool) $html->getElementById('unavailable');
    }

    private function _extractListIdFromRawHtml($raw): string
    {
        $html = new DOMDocument();
        $html->loadHTML(stream_get_contents($raw));
        if ($this->_checkIfPrivate($html)) {
            throw new PrivateListException("the watchlist of $this->target is private");
        }
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
        throw new ImdbRequestException("could not find listId");
    }

    private function _getList(string $id): array
    {
        $input = $this->client->fetchWithListId($id);
        if (!$input) {
            if (str_contains($http_response_header[0], "403")) {
                throw new PrivateListException("list $id is private");
            }
            throw new ImdbRequestException("Could not fetch list");
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
}