<?php

namespace Drupal\tmdb\CacheableTmdbRequest;

use Drupal\mvs\enum\Language;
use Drupal\mvs\enum\NodeBundle;
use Drupal\tmdb\TmdbLocalStorageFilePath;

class Genres extends CacheableTmdbRequest {

  private NodeBundle $bundle;

  private Language $lang;

  public function setBundle(NodeBundle $bundle): self {
    $this->bundle = $bundle;

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
    $api = $this->connect->getGenresApi();
    $params = ['language' => $this->lang->name];

    return NodeBundle::movie === $this->bundle ? $api->getMovieGenres($params) : $api->getTvGenres($params);
  }

  /**
   * {@inheritDoc}
   */
  protected function massageBeforeSave(array $data): array {
    return $data['genres'];
  }

  /**
   * {@inheritDoc}
   */
  protected function getStorageFilePath(): TmdbLocalStorageFilePath {
    return new TmdbLocalStorageFilePath(
      'genres',
      "{$this->bundle->name}_{$this->lang->name}"
    );
  }

}
