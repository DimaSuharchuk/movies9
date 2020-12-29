<?php

namespace Drupal\tmdb\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;

/**
 * @ExtraFieldDisplay(
 *   id = "title",
 *   label = @Translation("Extra: Title"),
 *   bundles = {"node.movie", "node.tv"}
 * )
 */
class Title extends ExtraTmdbFieldDisplayBase {

  /**
   * {@inheritDoc}
   */
  public function build(ContentEntityInterface $entity): array {
    /** @var \Drupal\node\NodeInterface $entity */
    return ['#markup' => $entity->getTitle()];
  }

}
