<?php

namespace Drupal\tmdb\CacheableTmdbRequest;

use Drupal\mvs\enum\Language;
use Drupal\tmdb\TmdbLocalStorageFilePath;

class Seasons extends CacheableTmdbRequest {

  private int $tv_tmdb_id;

  private int $season_number;

  private Language $lang;

  public function setTvTmdbId(int $tv_tmdb_id): self {
    $this->tv_tmdb_id = $tv_tmdb_id;

    return $this;
  }

  public function setSeasonNumber(int $season_number): self {
    $this->season_number = $season_number;

    return $this;
  }

  public function setLanguage(Language $lang): self {
    $this->lang = $lang;

    return $this;
  }

  /**
   * @inheritDoc
   */
  protected function request(): array {
    return $this->connect
      ->getTvSeasonApi()
      ->getSeason($this->tv_tmdb_id, $this->season_number, [
        'language' => $this->lang->name,
      ]);
  }

  /**
   * @inheritDoc
   */
  protected function massageBeforeSave(array $data): array {
    $season = [
      'title' => $data['name'],
      'overview' => $data['overview'],
      'poster_path' => $data['poster_path'],
    ];

    $allowed_episode_fields = [
      'air_date',
      'episode_number',
      'id',
      'name',
      'overview',
      'still_path',
    ];
    $season['episodes'] = $this->allowedFieldsFilter(
      $data['episodes'],
      $allowed_episode_fields
    );

    $allowed_star_fields = [
      'character',
      'gender',
      'id',
      'name',
      'profile_path',
    ];
    foreach ($season['episodes'] as $key => &$episode) {
      $episode['guest_stars'] = $this->allowedFieldsFilter(
        $data['episodes'][$key]['guest_stars'],
        $allowed_star_fields,
      );
    }

    // @todo Purge guest stars - save only TMDb IDs.

    return $season;
  }

  /**
   * @inheritDoc
   */
  protected function getStorageFilePath(): TmdbLocalStorageFilePath {
    return new TmdbLocalStorageFilePath(
      'seasons',
      "{$this->tv_tmdb_id}_{$this->season_number}_{$this->lang->name}"
    );
  }

}
