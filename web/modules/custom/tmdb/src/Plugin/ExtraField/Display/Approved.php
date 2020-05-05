<?php

namespace Drupal\tmdb\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
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
        '#markup' => new TranslatableMarkup('Approved'),
      ];
    }

    return $build;
  }

}
