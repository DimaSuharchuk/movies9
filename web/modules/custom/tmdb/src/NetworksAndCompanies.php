<?php

namespace Drupal\tmdb;

use Drupal\imdb\Constant;
use Drupal\tmdb\enum\TmdbImageFormat;

trait NetworksAndCompanies {

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
    $format = TmdbImageFormat::w200();

    $build = [];

    foreach ($items as $item) {
      if ($item['logo_path']) {
        $build[] = [
          '#theme' => 'image',
          '#uri' => Constant::TMDB_IMAGE_BASE_URL . $format->key() . $item['logo_path'],
          '#title' => $item['name'],
          '#alt' => $item['name'],
        ];
      }
    }

    return $build;
  }

}
