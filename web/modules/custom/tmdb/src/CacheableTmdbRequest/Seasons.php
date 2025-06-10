<?php

namespace Drupal\tmdb\CacheableTmdbRequest;

use Drupal\mvs\enum\Language;
use Drupal\tmdb\TmdbLocalStorageFilePath;

class Seasons extends CacheableTmdbRequest {

  /**
   * @param int $tv_tmdb_id
   * @param int $season_number
   * @param \Drupal\mvs\enum\Language $lang
   */
  public function __construct(
    private readonly int $tv_tmdb_id,
    private readonly int $season_number,
    private readonly Language $lang,
  ) {
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
      'runtime',
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
