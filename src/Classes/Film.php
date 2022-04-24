<?php

namespace Esenliyim\Listimporter\Classes;

use DateTime;

enum Prop: string {
    case Order = "order";
    case ImdbId = "imdbId";
    case AddedAt = "addedAt";
    case ChangedAt = "changedAt";
    case Description = "description";
    case Title = "title";
    case TitleType = "titleType";
    case DirectedBy = "directedBy";
    case YouRated = "youRated";
    case Rating = "rating";
    case Runtime = "runtime";
    case Year = "year";
    case Genres = "genres";
    case VoteCount = "voteCount";
    case URL = "url";
    case ReleaseDate = "releaseDate";
    case DateRated = "ratingDate";

    public static function getConverted(self $case, string $value) {
        return match ($case) {
            Prop::Order => $value ?: intval($value),
            Prop::AddedAt => new DateTime($value),
            Prop::ChangedAt => $value ?: new DateTime($value),
            Prop::DateRated => $value ?: new DateTime($value),
            Prop::DirectedBy => self::_toArray($value),
            Prop::YouRated => $value ?: intval($value),
            Prop::Rating => $value ?: floatval($value),
            Prop::Runtime => intval($value),
            Prop::Year => $value ?: intval($value),
            Prop::Genres => self::_toArray($value),
            Prop::VoteCount => $value ?: intval($value),
            Prop::ReleaseDate => $value ?: new DateTime($value),
            default => $value,
        };
    }

    private static function _toArray(string $multiples): array {
        $arr = [];
        $temp = explode(',', $multiples);
        foreach ($temp as $value) {
            $arr[] = trim($value);
        }
        return $arr;
    }
}

class Film {


    /**
     * Gotta have this because IMDb's header fields can be inconsistent. Have to map them to provide a consistent result.
     * 
     */
    const keyMap = [
        'position' =>  Prop::Order,
        'const' => Prop::ImdbId,
        'created' => Prop::AddedAt,
        'date added' => Prop::AddedAt,
        'modified' => Prop::ChangedAt,
        'description' => Prop::Description,
        'title' => Prop::Title,
        'title type' => Prop::TitleType,
        'directors' => Prop::DirectedBy,
        'you rated' => Prop::YouRated,
        'imdb rating' => Prop::Rating,
        'runtime (mins)' => Prop::Runtime,
        'year' => Prop::Year,
        'genres' => Prop::Genres,
        'num votes' => Prop::VoteCount,
        'num. votes' => Prop::VoteCount,
        'url' => Prop::URL,
        'release date' => Prop::ReleaseDate,
        'release date (month/day/year)' => Prop::ReleaseDate,
        'date rated' => Prop::DateRated,
    ];

    public function __construct(array $data) {
        $this->_setProps($data);
    }

    public function __get($name) {
        return isset($this->$name) ? $this->$name : null;
    }

    private function _setProps(array $data): void {
        foreach ($data as $key => $value) {
            $prop = static::keyMap[$key];
            $this->{$prop->value} = Prop::getConverted($prop, $value);
        }
    }
}
