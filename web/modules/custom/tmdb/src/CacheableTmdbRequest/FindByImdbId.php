<?php

namespace Drupal\tmdb\CacheableTmdbRequest;

use Drupal\mvs\enum\NodeBundle;
use Drupal\tmdb\TmdbLocalStorageFilePath;
use Tmdb\Exception\TmdbApiException;

class FindByImdbId extends CacheableTmdbRequest {

  private string $imdb_id;

  public function setImdbId(string $imdb_id): self {
    $this->imdb_id = $imdb_id;

    return $this;
  }

  /**
   * {@inheritDoc}
   */
  protected function request(): array {
    // Request to API.
    $response = $this->connect->getFindApi()
      ->findBy($this->imdb_id, ['external_source' => 'imdb_id']);

    foreach ($response as $k => $v) {
      if ($v) {
        switch ($k) {
          case 'movie_results':
            return [
              'type' => NodeBundle::movie->name,
              'tmdb_id' => $v[0]['id'],
            ];

          case 'tv_results':
            return [
              'type' => NodeBundle::tv->name,
              'tmdb_id' => $v[0]['id'],
            ];
        }
      }
    }

    throw new TmdbApiException(
      TmdbApiException::STATUS_RESOURCE_NOT_FOUND,
      sprintf('Find API not found results for IMDB ID "%s".', $this->imdb_id)
    );
  }

  /**
   * {@inheritDoc}
   */
  protected function massageBeforeSave(array $data): array {
    $data['type'] = $data['type']->name;

    return $data;
  }

  /**
   * {@inheritDoc}
   */
  protected function massageAfterLoad(array &$data): void {
    $data['type'] = NodeBundle::tryFrom($data['type']);
  }

  /**
   * {@inheritDoc}
   */
  protected function getStorageFilePath(): TmdbLocalStorageFilePath {
    return new TmdbLocalStorageFilePath('get_tmdb_id_from_imdb_id', $this->imdb_id);
  }

}
