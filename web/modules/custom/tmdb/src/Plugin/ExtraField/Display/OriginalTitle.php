<?php

namespace Drupal\tmdb\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\mvs\enum\Language;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;

/**
 * @ExtraFieldDisplay(
 *   id = "original_title",
 *   label = @Translation("Extra: Original title"),
 *   description = "",
 *   bundles = {"node.movie", "node.tv", "person.person"}
 * )
 */
class OriginalTitle extends ExtraTmdbFieldDisplayBase {

  /**
   * {@inheritDoc}
   */
  public function build(ContentEntityInterface $entity): array {
    // Show original title only for non-english content.
    if ($entity->language()->getId() === Language::en->name) {
      return [];
    }

    // Get title from Eng node.
    $entity = $entity->getTranslation(Language::en->name);

    return ['#markup' => $entity->label()];
  }

}
