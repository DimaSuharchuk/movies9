<?php

namespace Drupal\tmdb\CacheableTmdbRequest;

use Drupal\tmdb\TmdbLocalStorageFilePath;

class EpisodeImdbId extends CacheableTmdbRequest {

  private int $tv_tmdb_id;

  private int $season_number;

  private int $episode_number;

  public function setTvTmdbId(int $tv_tmdb_id): self {
    $this->tv_tmdb_id = $tv_tmdb_id;

    return $this;
  }

  public function setSeasonNumber(int $season_number): self {
    $this->season_number = $season_number;

    return $this;
  }

  public function setEpisodeNumber(int $episode_number): self {
    $this->episode_number = $episode_number;

    return $this;
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
      "{$this->tv_tmdb_id}_{$this->season_number}_{$this->episode_number}"
    );
  }

}
