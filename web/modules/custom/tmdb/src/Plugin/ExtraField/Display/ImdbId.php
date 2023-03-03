<?php

namespace Drupal\tmdb\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\tmdb\Plugin\ExtraTmdbFieldDisplayBase;

/**
 * @ExtraFieldDisplay(
 *   id = "imdb_id",
 *   label = @Translation("Extra: Imdb ID"),
 *   description = "",
 *   bundles = {"node.movie", "node.tv"}
 * )
 */
class ImdbId extends ExtraTmdbFieldDisplayBase {

  /**
   * {@inheritDoc}
   */
  public function build(ContentEntityInterface $entity): array {
    return [
      '#theme' => 'field_with_label',
      '#label' => 'imdb id',
      '#content' => $entity->{'field_imdb_id'}->value,
    ];
  }

}
