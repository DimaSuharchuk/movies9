<?php

namespace Drupal\tmdb\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\imdb\Constant;
use Drupal\tmdb\enum\TmdbImageFormat;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;

/**
 * @ExtraFieldDisplay(
 *   id = "production_companies",
 *   label = @Translation("Extra: Production companies"),
 *   bundles = {"node.movie", "node.tv"}
 * )
 */
class ProductionCompanies extends ExtraTmdbFieldDisplayBase {

  /**
   * {@inheritDoc}
   */
  public function build(ContentEntityInterface $entity): array {
    $build = [];

    if ($companies = $this->getFieldValue('production_companies')) {
      if ($content = $this->buildProductionCompanies($companies)) {
        $build = [
          '#theme' => 'field_with_label',
          '#label' => $this->t('production companies'),
          '#content' => $content,
        ];
      }
    }

    return $build;
  }

  /**
   * Build array of "Production companies" images.
   *
   * @param array $companies
   *   Array of companies from TMDb API.
   *
   * @return array
   *   Themed images for render.
   */
  private function buildProductionCompanies(array $companies): array {
    $format = TmdbImageFormat::w200;

    $build = [];

    foreach ($companies as $company) {
      if ($company['logo_path']) {
        $build[] = [
          '#theme' => 'image',
          '#uri' => Constant::TMDB_IMAGE_BASE_URL . $format . $company['logo_path'],
          '#title' => $company['name'],
          '#alt' => $company['name'],
        ];
      }
    }

    return $build;
  }

}
