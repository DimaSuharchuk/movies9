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
  public function build(ContentEntityInterface $node): array {
    /** @var \Drupal\node\NodeInterface $node */
    return ['#markup' => $node->getTitle()];
  }

}
