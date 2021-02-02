<?php

namespace Drupal\tmdb\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;

/**
 * @ExtraFieldDisplay(
 *   id = "approved",
 *   label = @Translation("Extra: Approved"),
 *   description = "",
 *   bundles = {"node.movie", "node.tv"}
 * )
 */
class Approved extends ExtraTmdbFieldDisplayBase {

  /**
   * {@inheritDoc}
   */
  public function build(ContentEntityInterface $entity): array {
    if (!$entity->{'field_approved'}->value) {
      return [];
    }

    return ['#markup' => $this->t('approved', [], ['context' => 'Field label'])];
  }

}
