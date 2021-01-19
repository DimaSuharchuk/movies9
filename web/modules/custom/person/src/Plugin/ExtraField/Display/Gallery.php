<?php

namespace Drupal\person\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;

/**
 * @ExtraFieldDisplay(
 *   id = "gallery",
 *   label = @Translation("Extra: Gallery"),
 *   bundles = {"person.person"},
 *   replaceable = true
 * )
 */
class Gallery extends ExtraTmdbFieldDisplayBase {

  /**
   * {@inheritDoc}
   */
  public function build(ContentEntityInterface $entity): array {
    $build = [];

    return $build;
  }

}
