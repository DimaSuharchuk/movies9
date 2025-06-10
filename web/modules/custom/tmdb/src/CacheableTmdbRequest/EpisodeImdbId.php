<?php

namespace Drupal\tmdb\CacheableTmdbRequest;

use Drupal\tmdb\TmdbLocalStorageFilePath;

class EpisodeImdbId extends CacheableTmdbRequest {

  /**
   * @param int $tv_tmdb_id
   * @param int $season_number
   * @param int $episode_number
   */
  public function __construct(
    private readonly int $tv_tmdb_id,
    private readonly int $season_number,
    private readonly int $episode_number,
  ) {
  }

  /**
   * @inheritDoc
   */
  protected function request(): array {
    return $this->connect
      ->getTvEpisodeApi()
      ->getExternalIds($this->tv_tmdb_id, $this->season_number, $this->episode_number);
  }

  /**
   * @inheritDoc
   */
  protected function massageBeforeSave(array $data): array {
    return ['imdb_id' => $data['imdb_id']];
  }

  /**
   * @inheritDoc
   */
  protected function getStorageFilePath(): TmdbLocalStorageFilePath {
    return new TmdbLocalStorageFilePath(
      'episode_imdb_ids',
      "{$this->tv_tmdb_id}_{$this->season_number}_$this->episode_number"
    );
  }

}
