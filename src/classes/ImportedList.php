<?php

namespace Esenliyim\Listimporter\Classes;

use ArrayIterator;
use Countable;
use DateTime;
use IteratorAggregate;
use Traversable;

class ImportedList implements IteratorAggregate, Countable
{
    const FILM_TYPES = ['tvMovie', 'movie', 'short', 'video'];
    const TV_SHOW_TYPES = ['tvSeries', 'tvShort', 'tvMiniSeries', 'tvSpecial'];
    const EPISODE_TYPES = ['tvEpisode'];

    protected $list = [];

    public function __construct(array $entries)
    {
        foreach($entries as $entry)
        {
            $this->list[] = new Film($entry);
        }
    }

    public function count(): int
    {
        return count($this->list);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->list);
    }

    /**
     * Filter the list to only return what can be considered a film.
     */
    public function filmsOnly(): array|null
    {
        return $this->_filterByTypes(static::FILM_TYPES);
    }


    /**
     * Filter the list to only return what can be considered a TV show.
     */
    public function tvShowsOnly(): array|null
    {
        return $this->_filterByTypes(static::TV_SHOW_TYPES);
    }


    /**
     * Filter the list to only return only episodes of TV shows.
     */
    public function tvEpisodesOnly(): array|null
    {
        return $this->_filterByTypes(static::EPISODE_TYPES);
    }


    /**
     * Filter the list by genre.
     * 
     * @param array $genres -> an aray of genres to be filtered for
     * @param bool $or -> if true, include titles that belong to any of the specified genres. Otherwise include titles that belong to ALL the specified titles
     */
    public function filterByGenres(array $genres, bool $or): array|null
    {
        if ($or) {
            return array_filter($this->list, fn($entry) => !empty(array_intersect($genres, $entry->genres)));
        } else {
            return array_filter($this->list, fn($entry) => !array_diff($genres, $entry->genres));
        }
    }

    /**
     * Filter the list to return only the titles whose release dates are in the past 
     */
    public function releasedOnly(): array|null
    {
        $today = date("Y-m-d");
        return array_filter($this->list, fn($entry) => $entry->releaseDate < $today);
    }


    /**
     * Filter the list to return only the titles whose release dates are in the future 
     */
    public function upcomingOnly(): array|null
    {
        $today = date("Y-m-d");
        return array_filter($this->list, fn($entry) => $entry->releaseDate > $today);
    }


    /**
     * Filter the list to return only the short films 
     */
    public function shortsOnly(): array|null
    {
        return $this->_filterByTypes(['short']);
    }

    private function _filterByTypes(array $types): array| null
    {
        return array_filter($this->list, fn($entry) => in_array($entry->titleType, $types));
    }

    /**
     * Filter the list to return only titles of the specified types 
     */
    public function only(array $types): array|null
    {
        return $this->_filterByTypes($types);
    }

}