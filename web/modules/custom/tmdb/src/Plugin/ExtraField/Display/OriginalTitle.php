<?php

namespace Drupal\tmdb\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;

/**
 * @ExtraFieldDisplay(
 *   id = "original_title",
 *   label = @Translation("Extra: Original title"),
 *   bundles = {"node.movie", "node.tv"}
 * )
 */
class OriginalTitle extends ExtraTmdbFieldDisplayBase {

  /**
   * {@inheritDoc}
   */
  public function build(ContentEntityInterface $node): array {
    $build = [];

    // Show original title only for non-english content.
    if ($node->language()->getId() !== 'en') {
      /** @var \Drupal\node\NodeInterface $node */
      $build = ['#markup' => $node->getTranslation('en')->getTitle()];
    }

    return $build;
  }

}
