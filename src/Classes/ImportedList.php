<?php

namespace Esenliyim\Listimporter\Classes;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

class ImportedList implements IteratorAggregate, Countable {
    const FILM_TYPES = ['tvMovie', 'movie', 'short', 'video'];
    const TV_SHOW_TYPES = ['tvSeries', 'tvShort', 'tvMiniSeries', 'tvSpecial'];
    const EPISODE_TYPES = ['tvEpisode', 'tvPilot', 'tvSpecial'];
    const ALL_GENRES = [
        'Action',
        'Adult',
        'Adventure',
        'Animation',
        'Biography',
        'Comedy',
        'Crime',
        'Documentary',
        'Drama',
        'Family',
        'Fantasy',
        'Film-Noir',
        'Game-Show',
        'History',
        'Horror',
        'Musical',
        'Music',
        'Mystery',
        'News',
        'Reality-TV',
        'Romance',
        'Sci-Fi',
        'Short',
        'Sport',
        'Talk-Show',
        'Thriller',
        'War',
        'Western'
    ];
    const ALL_TYPES = [
        'short',
        'movie',
        'tvEpisode',
        'tvSeries',
        'tvShort',
        'tvMovie',
        'tvMiniSeries',
        'tvSpecial',
        'video',
        'videoGame',
        'tvPilot',
    ];

    protected $genreFilter;
    protected $typeFilter;
    protected $releaseFilter;
    protected $genreLogic;

    protected $list = [];

    public function __construct(array $entries) {
        foreach ($entries as $entry) {
            $this->list[] = new Film($entry);
        }
    }

    public function count(): int {
        return count($this->list);
    }

    public function getIterator(): Traversable {
        return new ArrayIterator($this->list);
    }

    public function getAllowedTypes(): array {
        return static::ALL_TYPES;
    }

    public function getAllowedGenres(): array {
        return static::ALL_GENRES;
    }

    public function getAll(): array
    {
        return $this->list;
    }

    public function setGenreFilter(array $genres, bool $or = false): ImportedList {
        $accepted = array_intersect($genres, static::ALL_GENRES);
        if(empty($accepted)) {
            return $this;
        }
        $this->genreFilter = $accepted;
        $this->genreLogic = $or;
        return $this;
    }

    public function setTypeFilter(array $types): ImportedList {
        $accepted = array_intersect($types, static::ALL_TYPES);
        if (empty($accepted)) {
            return $this;
        }
        $this->typeFilter = $accepted;
        return $this;
    }

    public function setReleaseFiter(bool $released): ImportedList {
        $this->releaseFilter = $released;
        return $this;
    }

    public function getFiltered(): array|null {
        $filteredByRelease = isset($this->releaseFilter)
            ? ($this->releaseFilter ? $this->releasedOnly() : $this->upcomingOnly())
            : $this->list;
        $filteredByType = $this->typeFilter
            ? $this->_filterByTypes($this->typeFilter, $filteredByRelease)
            : $filteredByRelease;
        $filteredByGenre = $this->genreFilter 
            ? $this->_filterByGenres($this->genreFilter, $this->genreLogic, $filteredByType)
            : $filteredByType;
        return $filteredByGenre;
    }

    /**
     * Filter the list to only return what can be considered a film.
     */
    public function filmsOnly(): array|null {
        return $this->_filterByTypes(static::FILM_TYPES);
    }


    /**
     * Filter the list to only return what can be considered a TV show.
     */
    public function tvShowsOnly(): array|null {
        return $this->_filterByTypes(static::TV_SHOW_TYPES);
    }


    /**
     * Filter the list to only return only episodes of TV shows.
     */
    public function tvEpisodesOnly(): array|null {
        return $this->_filterByTypes(static::EPISODE_TYPES);
    }


    /**
     * Filter the list by genre.
     * 
     * @param array $genres -> an aray of genres to be filtered for
     * @param bool $or -> if true, include titles that belong to any of the specified genres. Otherwise include titles that belong to ALL the specified titles
     */
    public function filterByGenres(array $genres, bool $or = false): array|null {
        return $this->_filterByGenres($genres, $or, $this->list);
    }

    public function _filterByGenres(array $genres, bool $or, array $list): array|null {
        if ($or) {
            return array_filter($list, fn ($entry) => !empty(array_intersect($genres, $entry->genres)));
        } else {
            return array_filter($list, fn ($entry) => !array_diff($genres, $entry->genres));
        }
    }

    /**
     * Filter the list to return only the titles whose release dates are in the past 
     */
    public function releasedOnly(): array|null {
        $today = date("Y-m-d");
        return array_filter($this->list, fn ($entry) => $entry->releaseDate <= $today);
    }


    /**
     * Filter the list to return only the titles whose release dates are in the future 
     */
    public function upcomingOnly(): array|null {
        $today = date("Y-m-d");
        return array_filter($this->list, fn ($entry) => $entry->releaseDate > $today);
    }


    /**
     * Filter the list to return only the short films 
     */
    public function shortsOnly(): array|null {
        return $this->_filterByTypes(['short']);
    }

    private function _filterByTypes(array $types, array $list = null): array| null {
        $list = $list ?: $this->list;
        return array_filter($list, fn ($entry) => in_array($entry->titleType, $types));
    }

    /**
     * Filter the list to return only titles of the specified types 
     */
    public function only(array $types): array|null {
        return $this->_filterByTypes($types);
    }
}
