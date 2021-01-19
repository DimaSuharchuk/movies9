<?php

namespace Drupal\person\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;

/**
 * @ExtraFieldDisplay(
 *   id = "filmography",
 *   label = @Translation("Extra: Filmography"),
 *   bundles = {"person.person"},
 *   replaceable = true
 * )
 */
class Filmography extends ExtraTmdbFieldDisplayBase {

  /**
   * {@inheritDoc}
   */
  public function build(ContentEntityInterface $entity): array {
    $build = [];

    $build['acting'] = [
    ];
    $build['production'] = [
    ];

    return $build;
  }

}
