<?php

namespace Drupal\tmdb\Plugin;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\imdb\Constant;
use Drupal\tmdb\enum\TmdbImageFormat;

abstract class ExtraTmdbImageFieldDisplayBase extends ExtraTmdbFieldDisplayBase {

  /**
   * Allowed format from TMDb API.
   *
   * @param ContentEntityInterface $entity
   *   The field's parent entity.
   *
   * @return TmdbImageFormat
   */
  abstract public function getFormat(ContentEntityInterface $entity): TmdbImageFormat;

  /**
   * Image value from TMDb API starts from "/" symbol.
   *
   * @param ContentEntityInterface $entity
   *   The field's parent entity.
   *
   * @return string|null
   */
  abstract public function getImageValue(ContentEntityInterface $entity): ?string;

  /**
   * The string should be printed in "title" and "alt" attributes of <img> tag.
   *
   * @param ContentEntityInterface $entity
   *   The field's parent entity.
   *
   * @return string
   */
  abstract public function getImageAlt(ContentEntityInterface $entity): string;


  /**
   * {@inheritDoc}
   */
  public function build(ContentEntityInterface $entity): array {
    $build = [];

    if ($image_name = $this->getImageValue($entity)) {
      $alt = $this->getImageAlt($entity);
      $build = [
        '#theme' => 'image',
        '#uri' => Constant::TMDB_IMAGE_BASE_URL . $this->getFormat($entity)
            ->value() . $image_name,
        '#title' => $alt,
        '#alt' => $alt,
      ];
    }

    return $build;
  }

}
