<?php

namespace Drupal\tmdb\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;

/**
 * @ExtraFieldDisplay(
 *   id = "overview",
 *   label = @Translation("Extra: Overview"),
 *   description = "",
 *   bundles = {"node.movie", "node.tv"}
 * )
 */
class Overview extends ExtraTmdbFieldDisplayBase {

  /**
   * {@inheritDoc}
   */
  public function build(ContentEntityInterface $entity): array {
    $build = [];

    if ($overview = $this->getCommonFieldValue('overview')) {
      $build = ['#markup' => $overview];
    }

    return $build;
  }

}
