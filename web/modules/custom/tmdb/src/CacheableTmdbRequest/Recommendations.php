<?php

namespace Drupal\tmdb\CacheableTmdbRequest;

use Drupal\mvs\enum\Language;
use Drupal\mvs\enum\NodeBundle;
use Drupal\tmdb\TmdbLocalStorageFilePath;
use Tmdb\Exception\TmdbApiException;

class Recommendations extends CacheableTmdbRequest {

  private NodeBundle $bundle;

  private int $tmdb_id;

  private Language $lang;

  private int $page = 1;

  public function setBundle(NodeBundle $bundle): self {
    $this->bundle = $bundle;

    return $this;
  }

  public function setTmdbId(int $tmdb_id): self {
    $this->tmdb_id = $tmdb_id;

    return $this;
  }

  public function setLanguage(Language $lang): self {
    $this->lang = $lang;

    return $this;
  }

  public function setPage(int $page): self {
    $this->page = $page;

    return $this;
  }

  /**
   * {@inheritDoc}
   */
  protected function request(): array {
    // The first page usually cached already or recommendations better cache
    // with other fields for performance.
    if ($this->page === 1) {
      if (
        $response = (new FullRequest())
          ->setBundle($this->bundle)
          ->setTmdbId($this->tmdb_id)
          ->setLanguage($this->lang)
          ->response()
      ) {
        return $response['recommendations'];
      }

      throw new TmdbApiException(
        TmdbApiException::STATUS_RESOURCE_NOT_FOUND,
        sprintf(
          'Recommendations does not work for bundle %s TMDb ID %d.',
          $this->bundle->name,
          $this->tmdb_id
        )
      );
    }

    // Other pages cache with request to TMDb API.
    return $this
      ->nodeApi($this->bundle)
      ->getRecommendations($this->tmdb_id, [
          'language' => $this->lang->name,
          'page' => $this->page,
        ]
      );
  }

  /**
   * {@inheritDoc}
   */
  protected function massageBeforeSave(array $data): array {
    return $this->purgeRecommendationsFields($data);
  }

  /**
   * {@inheritDoc}
   */
  protected function getStorageFilePath(): TmdbLocalStorageFilePath {
    return new TmdbLocalStorageFilePath(
      'recommendations',
      "{$this->tmdb_id}_{$this->page}",
      [
        $this->lang->name,
        $this->bundle->name,
      ]
    );
  }

}
