# imdblistparser-php

Imports user (watch)lists from imdb.

## About

The `imdblistparser-php` package allows you to import the contents of publicly visible user lists from IMDb.

## Features

- Creates a container that contains all the titles from the specified list.

- Can fetch a user's watchlist

- Can filter the list by title type (movie, short, tv episode, etc...). Offers preset filters as well as the option to manually specify title types.

- Can filter unreleased or upcoming titles

- Can filter by genre, returning titles that either contain all of the specified genres or any of them

## TODO

- TESTS TESTS TESTS

- filter merging

## Notes

Can take several seconds to process when initialized with a user ID. That's because it requires 2 HTTP requests compared to the single one when importing directly via listId, and IMDb can take a while to respond to that one particular extra request. Providing the watchlist's listId will make it significantly faster, if you have the ID of course.

## Example usage

```php
use Esenliyim\Listimporter\ListImporter;

// To import via listId
$listIdImporter = new ListImporter("ls092287578");
$importedFromListId = $importer->fetchList();

$films = $importedFromListId->filmsOnly();

// Or via user ID
$userIdImporter = new ListImporter("ur115031818");
$importedFromUserId = $importer->fetchList();

$upcoming = $importedFromUserId->upcomingOnly();
```
## composer

SOON™