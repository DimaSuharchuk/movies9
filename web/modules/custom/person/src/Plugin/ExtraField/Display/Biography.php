<?php

namespace Drupal\person\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;

/**
 * @ExtraFieldDisplay(
 *   id = "biography",
 *   label = @Translation("Extra: Biography"),
 *   bundles = {"person.person"}
 * )
 */
class Biography extends ExtraTmdbFieldDisplayBase {

  /**
   * {@inheritDoc}
   */
  public function build(ContentEntityInterface $entity): array {
    $build = [];

    if ($bio = $this->getPersonCommonField('biography', TRUE)) {
      $build['#markup'] = $bio;
    }

    return $build;
  }

}
