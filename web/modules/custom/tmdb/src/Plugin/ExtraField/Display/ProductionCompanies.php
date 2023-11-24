<?php

namespace Drupal\tmdb\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\tmdb\NetworksAndCompanies;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;

/**
 * @ExtraFieldDisplay(
 *   id = "production_companies",
 *   label = @Translation("Extra: Production companies"),
 *   description = "",
 *   bundles = {"node.movie", "node.tv"}
 * )
 */
class ProductionCompanies extends ExtraTmdbFieldDisplayBase {

  use NetworksAndCompanies;

  /**
   * {@inheritDoc}
   */
  public function build(ContentEntityInterface $entity): array {
    $build = [];

    if (
      ($companies = $this->getCommonFieldValue('production_companies'))
      && ($content = $this->buildItems($companies))
    ) {
      $build = [
        '#theme' => 'field_with_label',
        '#label' => $this->t('production companies', [], ['context' => 'Field label']),
        '#content' => $content,
      ];
    }

    return $build;
  }

}
