<?php

namespace Drupal\tmdb\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;

/**
 * @ExtraFieldDisplay(
 *   id = "approved",
 *   label = @Translation("Extra: Approved"),
 *   bundles = {"node.movie", "node.tv"}
 * )
 */
class Approved extends ExtraTmdbFieldDisplayBase {

  /**
   * {@inheritDoc}
   */
  public function build(ContentEntityInterface $entity): array {
    $build = [];

    if ($a = $entity->{'field_approved'}->value) {
      $build = [
        '#markup' => $this->t('approved', [], ['context' => 'Field label']),
      ];
    }

    return $build;
  }

}
