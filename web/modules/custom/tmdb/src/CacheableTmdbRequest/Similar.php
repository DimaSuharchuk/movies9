<?php

namespace Drupal\tmdb\CacheableTmdbRequest;

use Drupal\mvs\enum\Language;
use Drupal\mvs\enum\NodeBundle;
use Drupal\tmdb\TmdbLocalStorageFilePath;
use Tmdb\Exception\TmdbApiException;

class Similar extends CacheableTmdbRequest {

  /**
   * @param \Drupal\mvs\enum\NodeBundle $bundle
   * @param int $tmdb_id
   * @param \Drupal\mvs\enum\Language $lang
   * @param int $page
   */
  public function __construct(
    private readonly NodeBundle $bundle,
    private readonly int $tmdb_id,
    private readonly Language $lang,
    private readonly int $page = 1,
  ) {
  }

  /**
   * {@inheritDoc}
   */
  protected function request(): array {
    // The first page usually cached already or similar better cache with other
    // fields for performance.
    if ($this->page === 1) {
      if ($response = new FullRequest($this->bundle, $this->tmdb_id, $this->lang)->response()) {
        return $response['similar'];
      }

      throw new TmdbApiException(
        TmdbApiException::STATUS_RESOURCE_NOT_FOUND,
        sprintf(
          'Similar does not work for bundle %s TMDb ID %d.',
          $this->bundle->name,
          $this->tmdb_id
        )
      );
    }

    // Other pages cache with request to TMDb API.
    return $this
      ->nodeApi($this->bundle)
      ->getSimilar($this->tmdb_id, [
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
      'similar',
      "{$this->tmdb_id}_$this->page",
      [
        $this->bundle->name,
        $this->lang->name,
      ]
    );
  }

}
