<?php

namespace Drupal\tmdb\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;

/**
 * @ExtraFieldDisplay(
 *   id = "original_title",
 *   label = @Translation("Extra: Original title"),
 *   bundles = {"node.movie", "node.tv", "person.person"}
 * )
 */
class OriginalTitle extends ExtraTmdbFieldDisplayBase {

  /**
   * {@inheritDoc}
   */
  public function build(ContentEntityInterface $entity): array {
    // Show original title only for non-english content.
    if ($entity->language()->getId() === 'en') {
      return [];
    }

    // Get title from Eng node.
    $entity = $entity->getTranslation('en');
    return ['#markup' => $entity->label()];
  }

}
