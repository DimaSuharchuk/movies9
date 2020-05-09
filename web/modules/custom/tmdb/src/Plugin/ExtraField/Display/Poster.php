<?php

namespace Drupal\tmdb\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\tmdb\enum\TmdbImageFormat;
use Drupal\tmdb\Plugin\ExtraTmdbImageFieldDisplayBase;

///**
// * @ExtraFieldDisplay(
// *   id = "poster",
// *   label = @Translation("Extra: Poster"),
// *   bundles = {"node.movie", "node.tv"}
// * )
// */

/**
 * @deprecated Movie and TV save poster in Drupal field.
 * @see \Drupal\imdb\Plugin\Field\FieldType\TmdbImageItem
 */
class Poster extends ExtraTmdbImageFieldDisplayBase {

  /**
   * {@inheritDoc}
   */
  public function getFormat(ContentEntityInterface $entity): TmdbImageFormat {
    return TmdbImageFormat::w300();
  }

  /**
   * {@inheritDoc}
   */
  public function getImageValue(ContentEntityInterface $entity): ?string {
    return $this->getFieldValue('poster_path');
  }

  /**
   * {@inheritDoc}
   */
  public function getImageAlt(ContentEntityInterface $entity): string {
    return $entity->{'title'}->value;
  }

}
