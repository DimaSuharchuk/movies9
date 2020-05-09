<?php

namespace Drupal\tmdb\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;

///**
// * @ExtraFieldDisplay(
// *   id = "genres",
// *   label = @Translation("Extra: Genres"),
// *   bundles = {"node.movie", "node.tv"}
// * )
// */

/**
 * @deprecated Movie and TV have Drupal field.
 */
class Genres extends ExtraTmdbFieldDisplayBase {

  /**
   * {@inheritDoc}
   */
  public function build(ContentEntityInterface $entity): array {
    $build = [];

    if ($genres = $this->getFieldValue('genres')) {
      $build = [
        '#theme' => 'item_list',
        '#items' => array_column($genres, 'name'),
      ];
    }

    return $build;
  }

}
