<?php

namespace Drupal\tmdb\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;

/**
 * @ExtraFieldDisplay(
 *   id = "title",
 *   label = @Translation("Extra: Title"),
 *   description = "",
 *   bundles = {"node.movie", "node.tv", "person.person"}
 * )
 */
class Title extends ExtraTmdbFieldDisplayBase {

  /**
   * {@inheritDoc}
   */
  public function build(ContentEntityInterface $entity): array {
    return ['#markup' => $entity->label()];
  }

}
