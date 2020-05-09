<?php

namespace Drupal\tmdb;

use Drupal\imdb\enum\Language;
use Drupal\imdb\enum\NodeBundle;
use Drupal\tmdb\enum\TmdbLocalStorageType;

class TmdbLocalStorageFilePath {

  private string $path;


  /**
   * Create path to file in TMDb Local Storage.
   *
   * @param NodeBundle $bundle
   * @param TmdbLocalStorageType $storage_type
   * @param Language $lang
   * @param int $tmdb_id
   * @param null $page
   *   Page using for pageable responses from TMDb API.
   *   At least for "recommendations" and "similar".
   */
  public function __construct(NodeBundle $bundle, TmdbLocalStorageType $storage_type, Language $lang, int $tmdb_id, $page = NULL) {
    // Build path.
    $path = "{$lang->value()}/{$bundle->value()}/{$storage_type->value()}/";
    if ($page) {
      $path .= "{$page}/";
    }
    $path .= $tmdb_id;

    $this->path = $path;
  }

  public function __toString(): string {
    return $this->path;
  }

}
