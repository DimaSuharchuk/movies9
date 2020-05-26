<?php

namespace Drupal\tmdb\CacheableTmdbRequest;

use Drupal\imdb\enum\Language;
use Drupal\tmdb\TmdbLocalStorageFilePath;

class MovieCollection extends CacheableTmdbRequest {

  private int $movie_tmdb_id;

  private Language $lang;


  public function setMovieTmdbId(int $movie_tmdb_id): self {
    $this->movie_tmdb_id = $movie_tmdb_id;
    return $this;
  }

  public function setLanguage(Language $lang): self {
    $this->lang = $lang;
    return $this;
  }


  /**
   * {@inheritDoc}
   */
  protected function request(): array {
    return $this->connect
      ->getCollectionsApi()
      ->getCollection($this->movie_tmdb_id, [
        'language' => $this->lang->value(),
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
      'teasers' => [],
    ];

    // Filter nested teasers.
    $allowed_fields = [
      'id',
      'title',
      'original_title',
      'poster_path',
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
      "{$this->movie_tmdb_id}_{$this->lang->key()}"
    );
  }

}
