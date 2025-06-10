<?php

namespace Drupal\tmdb\CacheableTmdbRequest;

use Drupal\mvs\enum\Language;
use Drupal\tmdb\TmdbLocalStorageFilePath;

class MovieCollection extends CacheableTmdbRequest {

  /**
   * @param int $movie_tmdb_id
   * @param \Drupal\mvs\enum\Language $lang
   */
  public function __construct(
    private readonly int $movie_tmdb_id,
    private readonly Language $lang,
  ) {
  }

  /**
   * {@inheritDoc}
   */
  protected function request(): array {
    return $this->connect
      ->getCollectionsApi()
      ->getCollection($this->movie_tmdb_id, [
        'language' => $this->lang->name,
      ]);
  }

  /**
   * {@inheritDoc}
   */
  protected function massageBeforeSave(array $data): array {
    // Filter collection fields.
    $filtered = [
      'id' => $data['id'],
      'name' => $data['name'],
      'poster_path' => $data['poster_path'],
    ];

    // Filter nested teasers.
    $allowed_fields = [
      'id',
      'title',
      'original_title',
      'poster_path',
      'vote_average',
    ];
    $filtered['teasers'] = $this->allowedFieldsFilter($data['parts'], $allowed_fields);

    return $filtered;
  }

  /**
   * {@inheritDoc}
   */
  protected function getStorageFilePath(): TmdbLocalStorageFilePath {
    return new TmdbLocalStorageFilePath(
      'movie_collection',
      "{$this->movie_tmdb_id}_{$this->lang->name}"
    );
  }

}
