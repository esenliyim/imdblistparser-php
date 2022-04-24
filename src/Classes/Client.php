<?php

namespace Esenliyim\Listimporter\Classes;

use Esenliyim\Listimporter\Exceptions\ImdbRequestException;
use Esenliyim\Listimporter\Exceptions\PrivateListException;

class Client
{
    const LIST_URL_TEMPLATE = "https://www.imdb.com/list/{listId}/export";
    const WATCHLIST_TEMPLATE = "https://www.imdb.com/user/{userId}/watchlist";

    public function fetchWithListId(string $id)
    {
        $url = str_replace("{listId}", $id, static::LIST_URL_TEMPLATE);
        $input = fopen($url, 'r');
        if (!$input) {
            if (str_contains($http_response_header[0], "403")) {
                throw new PrivateListException("list $id is private");
            }
            throw new ImdbRequestException("Could not fetch list");
        }
        return $input;
    }

    public function fetchWithUserId(string $id)
    {
        $url = str_replace("{userId}", $id, static::WATCHLIST_TEMPLATE);
        $input = fopen($url, 'r');
        if (!$input) {
            throw new ImdbRequestException("could not get list id");
        }
        return $input;
    }
}