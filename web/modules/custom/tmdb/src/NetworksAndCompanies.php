<?php

namespace Drupal\tmdb;

use Drupal\imdb\ImageBuilder;
use Drupal\tmdb\enum\TmdbImageFormat;

trait NetworksAndCompanies {

  use ImageBuilder;

  /**
   * Build array of "Production companies" or "Networks" from TMDb API.
   *
   * @param array $items
   *   Array of companies or networks from TMDb API.
   *
   * @return array
   *   Renderable array of logos.
   */
  private function buildItems(array $items): array {
    $build = [];

    foreach ($items as $item) {
      if ($item['logo_path']) {
        $build[] = $this->buildTmdbImageRenderableArray(
          TmdbImageFormat::w92(),
          $item['logo_path'],
          $item['name'],
        );
      }
    }

    return $build;
  }

}
